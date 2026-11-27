<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-tema="adultos">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Portal web de la Carrera de {{ config('app.name') }} - UAGRM">
        <title>{{ config('app.name', 'Administración de Empresas') }}</title>

        {{--
            Capa 1 - Presentación (SPA React). La configuración se inyecta aquí para que la aplicación
            funcione en la raíz o en un subdirectorio (p. ej. /inf513/grupo23sa/proyecto2/public).
        --}}
        <script>
            window.APP_CONFIG = {!! json_encode([
                'nombre' => config('app.name'),
                'base' => rtrim(parse_url(url('/'), PHP_URL_PATH) ?? '', '/'),
                'api' => rtrim(url('/api'), '/'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
        </script>

        {{-- Aplica el tema, el modo día/noche y la accesibilidad guardados antes de dibujar la página. --}}
        <script>
            (function () {
                var d = document.documentElement;
                var a = {};
                try { a = JSON.parse(localStorage.getItem('apariencia') || '{}') || {}; } catch (e) {}
                var temas = ['ninos', 'jovenes', 'adultos'];
                d.dataset.tema = temas.indexOf(a.tema) >= 0 ? a.tema : 'adultos';
                var modo = a.modo || 'auto';
                var hora = new Date().getHours();
                var noche = modo === 'noche' || (modo === 'auto' && (hora >= 19 || hora < 7));
                d.classList.toggle('dark', noche);
                d.style.colorScheme = noche ? 'dark' : 'light';
                if (a.contraste) { d.dataset.contraste = 'alto'; }
                var f = parseInt(a.fuente, 10);
                if (f >= 85 && f <= 150) { d.style.fontSize = f + '%'; }
            })();
        </script>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|fredoka:400,500,600,700|poppins:400,500,600,700&display=swap" rel="stylesheet">

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/main.tsx'])
    </head>
    <body class="font-sans antialiased">
        <noscript>Esta aplicación necesita JavaScript habilitado en su navegador.</noscript>
        <div id="app"></div>
    </body>
</html>
