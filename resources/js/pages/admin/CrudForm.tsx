import { useNavigate, useParams } from 'react-router';
import type { Valores } from '@/components/admin/CamposCrud';
import { EncabezadoPagina } from '@/components/admin/EncabezadoPagina';
import { EsqueletoFormulario, FormularioCrud } from '@/components/admin/FormularioCrud';
import type { RespuestaGuardar } from '@/components/admin/FormularioCrud';
import { usePanel } from '@/components/admin/panel';
import { conProgreso } from '@/components/admin/subidas';
import { ErrorCarga } from '@/components/ui/Cargando';
import { useAuth } from '@/contexts/AuthContext';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { api, construirFormData, contieneArchivos } from '@/lib/api';
import type { RespuestaCrudForm } from '@/types';

function EditorRegistro({ uri, id }: { uri: string; id: string | null }) {
    const navigate = useNavigate();
    const { recargarMenu } = usePanel();
    const { recargarUsuario } = useAuth();
    const esEdicion = id !== null;
    const { datos, error, estado, recargar } = useApi<RespuestaCrudForm>(esEdicion ? `admin/${uri}/${id}` : `admin/${uri}/formulario`);

    const config = datos?.config;
    // GET /api/admin/{uri}/{id} solo exige "ver": sin permiso de edición el registro se muestra en solo lectura.
    const soloLectura = esEdicion && !config?.permisos?.editar;
    const titulo = config
        ? esEdicion
            ? `${soloLectura ? 'Detalle de' : 'Editar'} ${config.singular}`
            : `${config.femenino ? 'Nueva' : 'Nuevo'} ${config.singular}`
        : 'Cargando…';

    useTitulo(config ? `${titulo} · ${config.titulo}` : titulo);

    if (!datos || !config) {
        return error ? <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} /> : <EsqueletoFormulario />;
    }

    const enviar = (valores: Valores, alProgresar: (p: number | null) => void) => {
        const ruta = esEdicion ? `admin/${uri}/${id}` : `admin/${uri}`;

        // Con archivos: multipart (en edición, POST con _method=PUT); sin archivos: JSON.
        if (contieneArchivos(valores)) {
            return api.post<RespuestaGuardar>(ruta, construirFormData(valores, esEdicion ? 'PUT' : undefined), conProgreso(alProgresar));
        }

        return esEdicion ? api.put<RespuestaGuardar>(ruta, valores) : api.post<RespuestaGuardar>(ruta, valores);
    };

    const alGuardar = () => {
        // El menú y los permisos pueden cambiar al editar opciones de menú, roles o la propia cuenta.
        if (uri === 'menu' || uri === 'roles') {
            recargarMenu();
        }

        if (uri === 'roles' || uri === 'usuarios') {
            void recargarUsuario();
        }

        void navigate(`/admin/${uri}`);
    };

    return (
        <div className="mx-auto max-w-5xl space-y-5">
            <EncabezadoPagina titulo={titulo} descripcion={config.descripcion} volver={{ to: `/admin/${uri}`, texto: config.titulo }} />
            <FormularioCrud
                config={config}
                campos={datos.campos ?? []}
                valores={datos.valores ?? {}}
                esEdicion={esEdicion}
                soloLectura={soloLectura}
                rutaListado={`/admin/${uri}`}
                enviar={enviar}
                alGuardar={alGuardar}
            />
        </div>
    );
}

/**
 * /admin/:uri/crear y /admin/:uri/:id/editar — formulario genérico del CRUD.
 * La clave reinicia el formulario al pasar de un registro o módulo a otro.
 */
export default function CrudForm() {
    const { uri = '', id } = useParams();

    return <EditorRegistro key={`${uri}:${id ?? 'nuevo'}`} uri={uri} id={id ?? null} />;
}
