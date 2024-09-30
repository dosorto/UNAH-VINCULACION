<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <label for="email">Correo electrónico</label>
    <input id="email" type="email" name="email" required>
    <button type="submit">Enviar enlace de restablecimiento</button>
    <span> {{ $status }} </span>
</form>
