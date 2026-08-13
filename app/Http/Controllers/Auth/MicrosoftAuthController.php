<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\NewUserOnboardingService;
use App\Support\Notification;
use App\Support\ProfileCompletion;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MicrosoftAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->isEnabled()) {
            return $this->fail('Login Microsoft no disponible.', 'La autenticacion con Microsoft no esta habilitada.');
        }

        if ($message = $this->configurationError()) {
            return $this->fail('Login Microsoft incompleto.', $message);
        }

        $state = Str::random(40);
        $request->session()->put('microsoft_oauth_state', $state);

        return redirect()->away($this->authorizeUrl($state));
    }

    public function callback(Request $request, NewUserOnboardingService $onboarding): RedirectResponse
    {
        if (! $this->isEnabled()) {
            return $this->fail('Login Microsoft no disponible.', 'La autenticacion con Microsoft no esta habilitada.');
        }

        if ($request->filled('error')) {
            return $this->fail(
                'No se pudo iniciar sesion con Microsoft.',
                (string) $request->query('error_description', $request->query('error'))
            );
        }

        $expectedState = (string) $request->session()->pull('microsoft_oauth_state', '');
        $receivedState = (string) $request->query('state', '');

        if ($expectedState === '' || $receivedState === '' || ! hash_equals($expectedState, $receivedState)) {
            return $this->fail('Sesion Microsoft invalida.', 'Vuelve a intentar iniciar sesion.');
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return $this->fail('Respuesta Microsoft invalida.', 'Microsoft no envio el codigo de autorizacion.');
        }

        try {
            $profile = $this->fetchMicrosoftProfile($code);
        } catch (RequestException $exception) {
            Log::warning('Microsoft login request failed', [
                'status' => $exception->response?->status(),
                'message' => $exception->getMessage(),
            ]);

            return $this->fail('No se pudo validar Microsoft.', 'Intenta nuevamente o contacta al administrador.');
        } catch (Throwable $exception) {
            Log::warning('Microsoft login failed unexpectedly', [
                'message' => $exception->getMessage(),
            ]);

            return $this->fail('No se pudo validar Microsoft.', 'Intenta nuevamente o contacta al administrador.');
        }

        $email = $this->profileEmail($profile);

        if (! $email) {
            return $this->fail('Microsoft no envio un correo valido.', 'Tu cuenta debe tener correo o userPrincipalName.');
        }

        if (! $this->domainIsAllowed($email)) {
            return $this->fail('Cuenta no autorizada.', 'Usa una cuenta institucional permitida para acceder al sistema.');
        }

        $microsoftId = (string) ($profile['id'] ?? '');

        if ($microsoftId === '') {
            return $this->fail('Perfil Microsoft incompleto.', 'Microsoft no envio el identificador de usuario.');
        }

        $user = $this->resolveUser($profile, $email, $microsoftId);

        if (! $user) {
            return $this->fail(
                'Usuario no registrado.',
                'Tu cuenta Microsoft fue validada, pero todavia no existe en el sistema. Contacta al administrador.'
            );
        }

        $requiresOnboarding = $onboarding->requiresEmployeeProfile($user);

        $user = $onboarding->prepareEmployeeProfile(
            $user,
            $this->profileEmployeeNumber($profile),
            $this->profileName($profile, $email),
        );

        Auth::login($user);
        $request->session()->regenerate();

        if ($requiresOnboarding || ProfileCompletion::isRequired($user)) {
            return redirect()->route('completar_perfil');
        }

        return redirect()->intended(route('inicio'));
    }

    private function fetchMicrosoftProfile(string $code): array
    {
        $tokenResponse = Http::asForm()
            ->acceptJson()
            ->post($this->tokenEndpoint(), [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
                'scope' => implode(' ', config('services.microsoft.scopes', [])),
            ])
            ->throw();

        $accessToken = (string) $tokenResponse->json('access_token', '');

        if ($accessToken === '') {
            throw new RequestException($tokenResponse);
        }

        $profile = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://graph.microsoft.com/v1.0/me', [
                '$select' => 'id,displayName,givenName,surname,mail,userPrincipalName,employeeId',
            ])
            ->throw()
            ->json();

        return is_array($profile) ? $profile : [];
    }

    private function resolveUser(array $profile, string $email, string $microsoftId): ?User
    {
        $user = User::where('microsoft_id', $microsoftId)->first();
        $userByEmail = User::where('email', $email)->first();

        if ($user && $userByEmail && $user->id !== $userByEmail->id) {
            Log::warning('Microsoft login email already belongs to another user', [
                'microsoft_user_id' => $user->id,
                'email_user_id' => $userByEmail->id,
                'email' => $email,
            ]);

            return $user;
        }

        $user ??= $userByEmail;

        if (! $user) {
            if (! config('services.microsoft.auto_create_users', false)) {
                return null;
            }

            $user = new User;
            $user->forceFill([
                'email' => $email,
                'email_verified_at' => now(),
            ]);
        }

        if ($user->microsoft_id && $user->microsoft_id !== $microsoftId) {
            Log::warning('Microsoft login id mismatch for existing email', [
                'user_id' => $user->id,
                'email' => $email,
            ]);

            return null;
        }

        $updates = [
            'microsoft_id' => $microsoftId,
            'name' => $this->toUsername($this->profileName($profile, $email)),
            'given_name' => $profile['givenName'] ?? null,
            'surname' => $profile['surname'] ?? null,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ];

        if (! $user->exists || ! User::where('email', $email)->whereKeyNot($user->getKey())->exists()) {
            $updates['email'] = $email;
        }

        $user->forceFill($updates)->save();

        return $user;
    }

    private function authorizeUrl(string $state): string
    {
        return $this->authorizationEndpoint().'?'.http_build_query([
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri(),
            'response_mode' => 'query',
            'scope' => implode(' ', config('services.microsoft.scopes', [])),
            'state' => $state,
            'prompt' => config('services.microsoft.prompt', 'select_account'),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function profileEmail(array $profile): ?string
    {
        $email = strtolower(trim((string) ($profile['mail'] ?? $profile['userPrincipalName'] ?? '')));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function profileName(array $profile, string $email): string
    {
        $name = trim((string) ($profile['displayName'] ?? ''));

        if ($name !== '') {
            return $name;
        }

        $name = trim(implode(' ', array_filter([
            $profile['givenName'] ?? null,
            $profile['surname'] ?? null,
        ])));

        return $name !== '' ? $name : $email;
    }

    private function toUsername(string $name): string
    {
        return str_replace(' ', '.', trim(preg_replace('/\s+/', ' ', $name)));
    }

    private function profileEmployeeNumber(array $profile): ?string
    {
        $employeeNumber = trim((string) ($profile['employeeId'] ?? ''));

        if ($employeeNumber === '') {
            Log::warning('Microsoft profile has no employeeId', [
                'microsoft_id' => $profile['id'] ?? null,
            ]);

            return null;
        }

        if (! preg_match('/^\d+$/', $employeeNumber)) {
            Log::warning('Microsoft profile employeeId is not numeric', [
                'microsoft_id' => $profile['id'] ?? null,
            ]);

            return null;
        }

        return $employeeNumber;
    }

    private function domainIsAllowed(string $email): bool
    {
        $allowedDomains = config('services.microsoft.allowed_domains', []);

        if ($allowedDomains === []) {
            return true;
        }

        $domain = strtolower(Str::afterLast($email, '@'));

        return in_array($domain, $allowedDomains, true);
    }

    private function configurationError(): ?string
    {
        if (! config('services.microsoft.client_id')) {
            return 'Falta MICROSOFT_CLIENT_ID.';
        }

        if (! config('services.microsoft.client_secret')) {
            return 'Falta MICROSOFT_CLIENT_SECRET.';
        }

        return null;
    }

    private function fail(string $title, string $body = ''): RedirectResponse
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->send();

        return redirect()->route('login');
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.microsoft.enabled', false);
    }

    private function redirectUri(): string
    {
        return config('services.microsoft.redirect') ?: route('login.microsoft.callback');
    }

    private function authorizationEndpoint(): string
    {
        return sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/authorize', $this->tenant());
    }

    private function tokenEndpoint(): string
    {
        return sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $this->tenant());
    }

    private function tenant(): string
    {
        return trim((string) config('services.microsoft.tenant', 'organizations')) ?: 'organizations';
    }
}
