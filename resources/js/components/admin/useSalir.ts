import { useCallback } from 'react';
import { flushSync } from 'react-dom';
import { useNavigate } from 'react-router';
import { toast } from 'sonner';
import { useAuth } from '@/contexts/AuthContext';
import { api, EVENTO_SESION_EXPIRADA } from '@/lib/api';

/**
 * Cierra la sesión (revoca el token en la API) y lleva a /login.
 * Con `todos` se cierran también las sesiones abiertas en otros dispositivos.
 *
 * La sesión vacía se aplica con un render síncrono antes de navegar: así las guardias de ruta
 * reaccionan primero y la navegación final a /login prevalece (sin "?redirigir=" a la página
 * anterior, que podría no corresponder al próximo usuario que inicie sesión).
 */
export function useSalir() {
    const { cerrarSesion } = useAuth();
    const navigate = useNavigate();

    return useCallback(
        async (todos = false) => {
            if (todos) {
                try {
                    await api.post('auth/logout', { todos: true });
                } catch {
                    // Aunque falle la red, la sesión se cierra en este navegador.
                }
            }

            await cerrarSesion();
            flushSync(() => {
                window.dispatchEvent(new CustomEvent(EVENTO_SESION_EXPIRADA));
            });
            toast.success(todos ? 'Se cerró la sesión en todos los dispositivos.' : 'Sesión cerrada correctamente.');
            void navigate('/login', { replace: true });
        },
        [cerrarSesion, navigate],
    );
}
