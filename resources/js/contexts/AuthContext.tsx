import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { api, estadoError, EVENTO_SESION_EXPIRADA, tokenSesion } from '@/lib/api';
import type { Usuario } from '@/types';

interface ValorAuth {
    usuario: Usuario | null;
    /** true mientras se verifica el token guardado al abrir la aplicación. */
    cargando: boolean;
    /** Inicia sesión contra POST /api/auth/login. Lanza el error de Axios si falla (422/429). */
    iniciarSesion: (email: string, password: string, recordar?: boolean) => Promise<Usuario>;
    /** Guarda una sesión ya obtenida (p. ej. al registrarse como graduado). */
    establecerSesion: (token: string, usuario: Usuario) => void;
    cerrarSesion: () => Promise<void>;
    /** Vuelve a leer GET /api/auth/usuario (p. ej. tras editar el perfil). */
    recargarUsuario: () => Promise<void>;
    actualizarUsuario: (usuario: Usuario) => void;
    /** ¿El rol del usuario tiene el permiso "{recurso}.{accion}"? */
    puede: (permiso: string) => boolean;
}

const AuthContext = createContext<ValorAuth | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [usuario, setUsuario] = useState<Usuario | null>(null);
    const [cargando, setCargando] = useState<boolean>(() => tokenSesion.obtener() !== null);

    const recargarUsuario = useCallback(async () => {
        if (!tokenSesion.obtener()) {
            setUsuario(null);

            return;
        }

        try {
            const { data } = await api.get<{ usuario: Usuario }>('auth/usuario');
            setUsuario(data.usuario);
        } catch (error) {
            // 403: la cuenta fue deshabilitada (el servidor ya revocó el token).
            if (estadoError(error) === 403) {
                tokenSesion.borrar();
            }

            setUsuario(null);
        }
    }, []);

    // Al abrir la aplicación se valida el token guardado.
    useEffect(() => {
        if (!tokenSesion.obtener()) {
            return;
        }

        recargarUsuario().finally(() => setCargando(false));
    }, [recargarUsuario]);

    // La API respondió 401: el token expiró o fue revocado.
    useEffect(() => {
        const alExpirar = () => setUsuario(null);
        window.addEventListener(EVENTO_SESION_EXPIRADA, alExpirar);

        return () => window.removeEventListener(EVENTO_SESION_EXPIRADA, alExpirar);
    }, []);

    const iniciarSesion = useCallback(async (email: string, password: string, recordar = false) => {
        const { data } = await api.post<{ token: string; usuario: Usuario }>('auth/login', { email, password, recordar });
        tokenSesion.guardar(data.token);
        setUsuario(data.usuario);

        return data.usuario;
    }, []);

    const establecerSesion = useCallback((token: string, nuevo: Usuario) => {
        tokenSesion.guardar(token);
        setUsuario(nuevo);
    }, []);

    const cerrarSesion = useCallback(async () => {
        try {
            await api.post('auth/logout');
        } catch {
            // Aunque falle la red, la sesión se cierra en el navegador.
        } finally {
            tokenSesion.borrar();
            setUsuario(null);
        }
    }, []);

    const puede = useCallback((permiso: string) => usuario?.permisos.includes(permiso) ?? false, [usuario]);

    const valor = useMemo<ValorAuth>(
        () => ({
            usuario,
            cargando,
            iniciarSesion,
            establecerSesion,
            cerrarSesion,
            recargarUsuario,
            actualizarUsuario: setUsuario,
            puede,
        }),
        [usuario, cargando, iniciarSesion, establecerSesion, cerrarSesion, recargarUsuario, puede],
    );

    return <AuthContext.Provider value={valor}>{children}</AuthContext.Provider>;
}

export function useAuth(): ValorAuth {
    const contexto = useContext(AuthContext);

    if (!contexto) {
        throw new Error('useAuth debe usarse dentro de <AuthProvider>.');
    }

    return contexto;
}
