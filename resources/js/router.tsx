import type { ComponentType } from 'react';
import { createBrowserRouter } from 'react-router';
import { RequierePanel, RequiereSesion, SoloInvitados } from '@/components/Guardias';
import { CargandoInicial, ErrorRuta, Raiz } from '@/components/Raiz';
import { config } from '@/lib/config';
import NoEncontrado from '@/pages/NoEncontrado';

/*
 * Rutas de la SPA (Capa 1). Cada página se descarga solo cuando se visita (code splitting).
 * Las páginas obtienen sus datos de la API RESTful con Axios (hooks/useApi, lib/api).
 */
const pagina = (cargar: () => Promise<{ default: ComponentType }>) => async () => ({ Component: (await cargar()).default });

export const router = createBrowserRouter(
    [
        {
            element: <Raiz />,
            errorElement: <ErrorRuta />,
            HydrateFallback: CargandoInicial,
            children: [
                // ------------------------------------------------ Sitio público
                {
                    lazy: pagina(() => import('@/layouts/SitioLayout')),
                    children: [
                        { index: true, lazy: pagina(() => import('@/pages/sitio/Inicio')) },
                        // CU3 - Información institucional y académica
                        { path: 'institucional/:slug', lazy: pagina(() => import('@/pages/sitio/Pagina')) },
                        { path: 'academico/:slug', lazy: pagina(() => import('@/pages/sitio/Pagina')) },
                        { path: 'plantel-docente', lazy: pagina(() => import('@/pages/sitio/Plantel')) },
                        { path: 'malla-curricular', lazy: pagina(() => import('@/pages/sitio/Malla')) },
                        { path: 'calendario-academico', lazy: pagina(() => import('@/pages/sitio/Calendario')) },
                        { path: 'preguntas-frecuentes', lazy: pagina(() => import('@/pages/sitio/Preguntas')) },
                        // CU4 - Biblioteca, investigación y Revista INNOVAR
                        { path: 'biblioteca', lazy: pagina(() => import('@/pages/sitio/Documentos')) },
                        { path: 'investigacion', lazy: pagina(() => import('@/pages/sitio/Investigacion')) },
                        { path: 'investigacion/:slug', lazy: pagina(() => import('@/pages/sitio/Proyecto')) },
                        { path: 'revista-innovar', lazy: pagina(() => import('@/pages/sitio/Revista')) },
                        { path: 'revista-innovar/normas-para-autores', lazy: pagina(() => import('@/pages/sitio/RevistaNormas')) },
                        { path: 'revista-innovar/:numero', lazy: pagina(() => import('@/pages/sitio/RevistaEdicion')) },
                        // CU5 - Vinculación, INNOVA y graduados
                        { path: 'vinculacion', lazy: pagina(() => import('@/pages/sitio/Vinculacion')) },
                        { path: 'innova', lazy: pagina(() => import('@/pages/sitio/Innova')) },
                        { path: 'graduados', lazy: pagina(() => import('@/pages/sitio/Graduados')) },
                        // CU6 - Noticias, eventos y galería
                        { path: 'noticias', lazy: pagina(() => import('@/pages/sitio/Noticias')) },
                        { path: 'noticias/:slug', lazy: pagina(() => import('@/pages/sitio/Noticia')) },
                        { path: 'eventos', lazy: pagina(() => import('@/pages/sitio/Eventos')) },
                        { path: 'eventos/:slug', lazy: pagina(() => import('@/pages/sitio/Evento')) },
                        { path: 'galeria', lazy: pagina(() => import('@/pages/sitio/Galeria')) },
                        { path: 'galeria/:slug', lazy: pagina(() => import('@/pages/sitio/Album')) },
                        // CU7 - Contacto y búsqueda global
                        { path: 'contacto', lazy: pagina(() => import('@/pages/sitio/Contacto')) },
                        { path: 'buscar', lazy: pagina(() => import('@/pages/sitio/Buscar')) },
                        // Páginas que requieren sesión (graduados y cuentas sin panel)
                        {
                            element: <RequiereSesion />,
                            children: [
                                { path: 'graduados/mi-ficha', lazy: pagina(() => import('@/pages/sitio/MiFicha')) },
                                { path: 'cuenta', lazy: pagina(() => import('@/pages/cuenta/Cuenta')) },
                            ],
                        },
                        { path: '*', Component: NoEncontrado },
                    ],
                },

                // ------------------------------------------------ Autenticación
                {
                    lazy: pagina(() => import('@/layouts/AuthLayout')),
                    children: [
                        {
                            element: <SoloInvitados />,
                            children: [
                                { path: 'login', lazy: pagina(() => import('@/pages/auth/Login')) },
                                { path: 'olvide-contrasena', lazy: pagina(() => import('@/pages/auth/OlvideContrasena')) },
                            ],
                        },
                        { path: 'restablecer-contrasena/:token', lazy: pagina(() => import('@/pages/auth/RestablecerContrasena')) },
                    ],
                },

                // ------------------------------------------------ Panel de administración (RBAC)
                {
                    path: 'admin',
                    element: <RequierePanel />,
                    children: [
                        {
                            lazy: pagina(() => import('@/layouts/AdminLayout')),
                            children: [
                                { index: true, lazy: pagina(() => import('@/pages/admin/Dashboard')) },
                                { path: 'reportes', lazy: pagina(() => import('@/pages/admin/Reportes')) },
                                { path: 'bitacora', lazy: pagina(() => import('@/pages/admin/Bitacora')) },
                                { path: 'roles/matriz', lazy: pagina(() => import('@/pages/admin/MatrizAcceso')) },
                                { path: 'configuracion/:grupo', lazy: pagina(() => import('@/pages/admin/Configuracion')) },
                                { path: 'albumes/:id/fotos', lazy: pagina(() => import('@/pages/admin/Fotos')) },
                                { path: 'cuenta', lazy: pagina(() => import('@/pages/cuenta/Cuenta')) },
                                // CRUD genérico: /admin/noticias, /admin/noticias/crear, /admin/noticias/5/editar
                                { path: ':uri', lazy: pagina(() => import('@/pages/admin/CrudIndex')) },
                                { path: ':uri/crear', lazy: pagina(() => import('@/pages/admin/CrudForm')) },
                                { path: ':uri/:id/editar', lazy: pagina(() => import('@/pages/admin/CrudForm')) },
                                { path: '*', Component: NoEncontrado },
                            ],
                        },
                    ],
                },
            ],
        },
    ],
    { basename: config.base || undefined },
);
