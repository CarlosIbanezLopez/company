<?php

namespace App\Providers;

use App\Models\Pagina;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
     *
     * El inicio y cierre de sesión ya no se registran con eventos: los registra
     * Api\AuthController (bitácora + tabla inicios_sesion) al emitir o revocar el token.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurarRestablecimiento();

        // El menú público se arma con las páginas publicadas: se limpia al cambiarlas.
        Pagina::saved(fn () => Cache::forget('sitio.menu'));
        Pagina::deleted(fn () => Cache::forget('sitio.menu'));
    }

    /**
     * El enlace del correo "Restablecer contraseña" abre la página de la SPA:
     * {APP_URL}/restablecer-contrasena/{token}?email=...
     */
    protected function configurarRestablecimiento(): void
    {
        ResetPassword::createUrlUsing(
            fn ($usuario, string $token) => url('/restablecer-contrasena/'.$token).'?email='.urlencode($usuario->getEmailForPasswordReset())
        );
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

        // Contraseñas: mínimo 8 caracteres; en producción además letras mayúsculas, minúsculas y números.
        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(8)->max(72)->letters()->mixedCase()->numbers()
            : Password::min(8)->max(72),
        );
    }
}
