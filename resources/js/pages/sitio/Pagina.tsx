import { useLocation, useParams } from 'react-router';
import { PaginaContenido } from '@/components/sitio/PaginaContenido';

/**
 * Páginas institucionales (/institucional/:slug) y académicas (/academico/:slug).
 * La sección se deduce del primer segmento de la ruta; la API usa la misma URI.
 */
export default function Pagina() {
    const { slug = '' } = useParams();
    const { pathname } = useLocation();
    const prefijo = pathname.split('/').filter(Boolean)[0] === 'academico' ? 'academico' : 'institucional';
    const ruta = `${prefijo}/${encodeURIComponent(slug)}`;

    // La clave reinicia la consulta al pasar de una página a otra.
    return <PaginaContenido key={ruta} ruta={ruta} />;
}
