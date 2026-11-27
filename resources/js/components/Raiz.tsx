import { CircleAlert, LoaderCircle, RotateCcw } from 'lucide-react';
import { isRouteErrorResponse, Link, Outlet, ScrollRestoration, useNavigation, useRouteError } from 'react-router';
import { Toaster } from 'sonner';
import { useApariencia } from '@/contexts/AparienciaContext';
import { NoEncontradoBloque } from '@/components/ui/Cargando';

/** Elemento raíz: restauración de scroll, barra de progreso entre páginas y notificaciones. */
export function Raiz() {
    const navegacion = useNavigation();
    const { modoEfectivo } = useApariencia();

    return (
        <>
            <ScrollRestoration />
            {navegacion.state === 'loading' && (
                <div className="fixed inset-x-0 top-0 z-[200] h-1 overflow-hidden bg-primary/20" role="progressbar" aria-label="Cargando página">
                    <div className="h-full w-1/3 animate-[progreso_1s_ease-in-out_infinite] bg-marca" />
                </div>
            )}
            <Outlet />
            <Toaster position="top-right" richColors closeButton theme={modoEfectivo === 'noche' ? 'dark' : 'light'} />
            <style>{'@keyframes progreso{0%{transform:translateX(-100%)}100%{transform:translateX(300%)}}'}</style>
        </>
    );
}

/** Pantalla mientras se descarga la primera página. */
export function CargandoInicial() {
    return (
        <div className="flex min-h-svh items-center justify-center gap-3 text-sm text-muted-foreground" role="status">
            <LoaderCircle className="size-5 animate-spin text-primary" aria-hidden="true" /> Cargando…
        </div>
    );
}

/** Errores de navegación (página inexistente, fallo al descargar un módulo, excepción en una vista). */
export function ErrorRuta() {
    const error = useRouteError();

    if (isRouteErrorResponse(error) && error.status === 404) {
        return <NoEncontradoBloque className="min-h-svh justify-center" />;
    }

    const nuevaVersion = error instanceof Error && /dynamically imported module|Failed to fetch|Importing a module script failed/i.test(error.message);

    if (import.meta.env.DEV) {
        console.error(error);
    }

    return (
        <div role="alert" className="mx-auto flex min-h-svh max-w-xl flex-col items-center justify-center gap-4 px-4 text-center">
            <CircleAlert className="size-12 text-destructive" aria-hidden="true" />
            <h1 className="text-2xl font-bold">{nuevaVersion ? 'Hay una nueva versión del sitio' : 'Ocurrió un error inesperado'}</h1>
            <p className="text-sm text-muted-foreground">
                {nuevaVersion ? 'Recargue la página para obtener la versión más reciente.' : 'Intente recargar la página. Si el problema continúa, vuelva al inicio.'}
            </p>
            <div className="flex gap-2">
                <button type="button" className="boton" onClick={() => window.location.reload()}>
                    <RotateCcw className="size-4" aria-hidden="true" /> Recargar
                </button>
                <Link to="/" className="boton-borde" reloadDocument>
                    Ir al inicio
                </Link>
            </div>
        </div>
    );
}
