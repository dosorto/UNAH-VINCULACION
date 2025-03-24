<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Policies\UnidadAcademica\FacultadCentroPolicy;
use Illuminate\Support\Facades\View;
use App\Clases\DataNavBar; // Importa la clase

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        FacultadCentro::class => FacultadCentroPolicy::class,
    ];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('DataNavBar', DataNavBar::class);
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', \SocialiteProviders\Microsoft\Provider::class);
        });
        
    }
}
