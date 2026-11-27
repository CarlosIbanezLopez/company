import { useParams } from 'react-router';
import type { Valores } from '@/components/admin/CamposCrud';
import { EncabezadoPagina } from '@/components/admin/EncabezadoPagina';
import { EsqueletoFormulario, FormularioCrud } from '@/components/admin/FormularioCrud';
import type { RespuestaGuardar } from '@/components/admin/FormularioCrud';
import { conProgreso } from '@/components/admin/subidas';
import { ConPermiso } from '@/components/Guardias';
import { ErrorCarga } from '@/components/ui/Cargando';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { api, construirFormData, contieneArchivos } from '@/lib/api';
import type { RespuestaCrudForm } from '@/types';

/** CU2 - Configuración del portal por grupo: "portal" (página principal) o "contacto" (contacto y redes). */
function FormularioConfiguracion({ grupo }: { grupo: string }) {
    const { datos, error, estado, recargar } = useApi<RespuestaCrudForm>(`admin/configuracion/${grupo}`);
    const config = datos?.config;

    useTitulo(config?.titulo ?? 'Configuración del portal');

    if (!datos || !config) {
        return error ? <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} /> : <EsqueletoFormulario />;
    }

    const enviar = (valores: Valores, alProgresar: (p: number | null) => void) =>
        contieneArchivos(valores)
            ? api.post<RespuestaGuardar>(`admin/configuracion/${grupo}`, construirFormData(valores, 'PUT'), conProgreso(alProgresar))
            : api.put<RespuestaGuardar>(`admin/configuracion/${grupo}`, valores);

    return (
        <div className="mx-auto max-w-5xl space-y-5">
            <EncabezadoPagina titulo={config.titulo} descripcion={config.descripcion} />
            <FormularioCrud
                config={config}
                campos={datos.campos ?? []}
                valores={datos.valores ?? {}}
                esEdicion
                soloLectura={!config.permisos?.editar}
                textoGuardar="Guardar configuración"
                enviar={enviar}
            />
        </div>
    );
}

export default function Configuracion() {
    const { grupo = '' } = useParams();

    return (
        <ConPermiso permiso="portal.ver">
            <FormularioConfiguracion key={grupo} grupo={grupo} />
        </ConPermiso>
    );
}
