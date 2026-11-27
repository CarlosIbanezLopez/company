import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router/dom';
import { AparienciaProvider } from '@/contexts/AparienciaContext';
import { AuthProvider } from '@/contexts/AuthContext';
import { SitioProvider } from '@/contexts/SitioContext';
import { router } from '@/router';

/*
 * Punto de entrada de la Capa 1 (Presentación): SPA React que consume la API RESTful de Laravel.
 */
const raiz = document.getElementById('app');

if (raiz) {
    createRoot(raiz).render(
        <StrictMode>
            <AuthProvider>
                <AparienciaProvider>
                    <SitioProvider>
                        <RouterProvider router={router} />
                    </SitioProvider>
                </AparienciaProvider>
            </AuthProvider>
        </StrictMode>,
    );
}
