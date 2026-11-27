import { Navigate, Outlet, useLocation, useSearchParams } from 'react-router';
import { Cargando, ErrorCarga } from '@/components/ui/Cargando';
import { useAuth } from '@/contexts/AuthContext';

/** Solo deja pasar a usuarios con sesión; si no, envía a /login?redirigir=... */
export function RequiereSesion() {
    const { usuario, cargando } = useAuth();
    const ubicacion = useLocation();

    if (cargando) {
        return <Cargando texto="Verificando sesión…" className="min-h-[50vh]" />;
    }

    if (!usuario) {
        const destino = `${ubicacion.pathname}${ubicacion.search}`;

        return <Navigate to={`/login?redirigir=${encodeURIComponent(destino)}`} replace />;
    }

    return <Outlet />;
}

/** Solo deja pasar a roles con acceso al panel (los graduados van a su ficha). */
export function RequierePanel() {
    const { usuario, cargando } = useAuth();
    const ubicacion = useLocation();

    if (cargando) {
        return <Cargando texto="Verificando sesión…" className="min-h-[50vh]" />;
    }

    if (!usuario) {
        return <Navigate to={`/login?redirigir=${encodeURIComponent(ubicacion.pathname + ubicacion.search)}`} replace />;
    }

    if (!usuario.panel) {
        return <Navigate to={usuario.es_graduado ? '/graduados/mi-ficha' : '/cuenta'} replace />;
    }

    return <Outlet />;
}

/**
 * Envía a los usuarios ya autenticados fuera de las páginas de inicio de sesión:
 * a la página pedida en ?redirigir= (solo rutas internas; las del panel solo si el rol tiene panel)
 * o al inicio que corresponde a su tipo de cuenta.
 */
export function SoloInvitados() {
    const { usuario, cargando } = useAuth();
    const [params] = useSearchParams();

    if (cargando) {
        return <Cargando texto="Verificando sesión…" className="min-h-[50vh]" />;
    }

    if (usuario) {
        const pedido = params.get('redirigir');
        const seguro = pedido && pedido.startsWith('/') && !pedido.startsWith('//') && !pedido.startsWith('/login') ? pedido : null;
        const porDefecto = usuario.panel ? '/admin' : usuario.es_graduado ? '/graduados/mi-ficha' : '/';
        const destino = seguro && (usuario.panel || !/^\/admin(\/|$)/.test(seguro)) ? seguro : porDefecto;

        return <Navigate to={destino} replace />;
    }

    return <Outlet />;
}

/** Muestra el contenido solo si el usuario tiene el permiso indicado (matriz de acceso). */
export function ConPermiso({ permiso, children }: { permiso: string; children: React.ReactNode }) {
    const { puede } = useAuth();

    return puede(permiso) ? <>{children}</> : <ErrorCarga estado={403} />;
}
