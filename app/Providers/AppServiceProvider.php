<?php

namespace App\Providers;

use App\Models\Bitacora;
use App\Models\Pagina;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
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
        $this->configureDefaults();
        $this->registrarEventos();
    }

    /**
     * Inicio y cierre de sesión quedan registrados en la bitácora (RF-19).
     */
    protected function registrarEventos(): void
    {
        Event::listen(Login::class, function (Login $evento) {
            Bitacora::registrar('inicio_sesion', 'Autenticación', 'Inició sesión: '.$evento->user->email, $evento->user->getAuthIdentifier(), $evento->user->getAuthIdentifier());
        });

        Event::listen(Logout::class, function (Logout $evento) {
            if ($evento->user) {
                Bitacora::registrar('cierre_sesion', 'Autenticación', 'Cerró sesión: '.$evento->user->email, $evento->user->getAuthIdentifier(), $evento->user->getAuthIdentifier());
            }
        });

        // El menú público se arma con las páginas publicadas: se limpia al cambiarlas.
        Pagina::saved(fn () => Cache::forget('sitio.menu'));
        Pagina::deleted(fn () => Cache::forget('sitio.menu'));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
