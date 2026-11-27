import { useParams } from 'react-router';
import { ListadoCrud } from '@/components/admin/ListadoCrud';

/**
 * /admin/:uri — listado genérico de cualquier módulo del panel (noticias, usuarios, roles...).
 * La clave `uri` reinicia el estado (búsqueda, datos) al pasar de un módulo a otro.
 */
export default function CrudIndex() {
    const { uri = '' } = useParams();

    return <ListadoCrud key={uri} uri={uri} />;
}
