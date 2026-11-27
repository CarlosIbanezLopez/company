import { NoEncontradoBloque } from '@/components/ui/Cargando';
import { useTitulo } from '@/hooks/useTitulo';

export default function NoEncontrado() {
    useTitulo('Página no encontrada');

    return <NoEncontradoBloque />;
}
