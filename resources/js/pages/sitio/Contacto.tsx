import { Clock, Mail, MapPin, MessageCircle, Phone, Send } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { Antispam } from '@/components/sitio/Antispam';
import { EnlaceExterno } from '@/components/sitio/Enlace';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import { REGLA_TELEFONO, revisarErrorAntispam, useAntispam } from '@/components/sitio/formularios';
import { RedesSociales } from '@/components/sitio/RedesSociales';
import type { RespuestaContacto, RespuestaEnvio } from '@/components/sitio/tipos';
import { useSitio } from '@/contexts/SitioContext';
import { useApi } from '@/hooks/useApi';
import { useFormulario } from '@/hooks/useFormulario';
import { useTitulo } from '@/hooks/useTitulo';
import { api } from '@/lib/api';
import type { Etiquetas } from '@/lib/validacion';

const CONSULTA_VACIA = { nombre: '', correo: '', telefono: '', categoria_consulta_id: '', mensaje: '', antispam_respuesta: '', sitio_web: '' };

const ETIQUETAS: Etiquetas = {
    nombre: 'nombre completo',
    correo: 'correo electrónico',
    telefono: 'teléfono',
    categoria_consulta_id: 'categoría',
    mensaje: 'mensaje',
    antispam_respuesta: 'verificación',
};

/** Información oficial de contacto, mapa y formulario de consultas (RF-15, RF-16). */
export default function Contacto() {
    useTitulo('Contacto');
    const { sitio } = useSitio();
    const { contacto, redes } = sitio;
    const consulta = useApi<RespuestaContacto>('contacto');
    const mapa = consulta.datos?.mapa ?? null;
    const whatsapp = (contacto.whatsapp ?? '').replace(/\D/g, '');

    return (
        <>
            <Encabezado titulo="Contacto" descripcion="Ubicación, datos de contacto, redes sociales y formulario de consultas." migas={[{ titulo: 'Contacto' }]} />

            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-5">
                <aside className="space-y-6 lg:col-span-2" aria-label="Datos de contacto">
                    <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                        {contacto.direccion && (
                            <DatoContacto icono={MapPin} etiqueta="Dirección">
                                {contacto.direccion}
                            </DatoContacto>
                        )}
                        {contacto.telefonos && (
                            <DatoContacto icono={Phone} etiqueta="Teléfonos">
                                {contacto.telefonos}
                            </DatoContacto>
                        )}
                        {contacto.correo && (
                            <DatoContacto icono={Mail} etiqueta="Correo">
                                <a href={`mailto:${contacto.correo}`} className="break-all text-primary hover:underline">
                                    {contacto.correo}
                                </a>
                            </DatoContacto>
                        )}
                        {contacto.horario && (
                            <DatoContacto icono={Clock} etiqueta="Horario de atención">
                                {contacto.horario}
                            </DatoContacto>
                        )}
                        {whatsapp && (
                            <DatoContacto icono={MessageCircle} etiqueta="WhatsApp">
                                <EnlaceExterno href={`https://wa.me/${whatsapp}`} className="text-primary hover:underline">
                                    Escribir por WhatsApp
                                </EnlaceExterno>
                            </DatoContacto>
                        )}
                        {Object.values(redes).some(Boolean) && (
                            <li className="p-5">
                                <p className="mb-3 text-xs font-bold text-muted-foreground uppercase">Redes sociales</p>
                                <RedesSociales redes={redes} />
                            </li>
                        )}
                    </ul>
                    {mapa && (
                        <div className="aspect-[4/3] overflow-hidden rounded-lg border">
                            <iframe src={mapa} title="Ubicación de la Carrera en el mapa" className="h-full w-full" loading="lazy" referrerPolicy="no-referrer-when-downgrade" />
                        </div>
                    )}
                </aside>

                <section className="lg:col-span-3" aria-labelledby="titulo-consulta">
                    <h2 id="titulo-consulta" className="text-2xl font-bold">
                        Envíenos su consulta
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">Responderemos a su correo electrónico. Los campos con * son obligatorios.</p>
                    <div className="mt-6">
                        <BloqueDatos consulta={consulta}>{(datos) => <FormContacto datos={datos} />}</BloqueDatos>
                    </div>
                </section>
            </div>
        </>
    );
}

function DatoContacto({ icono: IconoDato, etiqueta, children }: { icono: LucideIcon; etiqueta: string; children: ReactNode }) {
    return (
        <li className="flex gap-3 p-5">
            <IconoDato className="size-5 shrink-0 text-marca" aria-hidden="true" />
            <div className="min-w-0">
                <p className="text-xs font-bold text-muted-foreground uppercase">{etiqueta}</p>
                <div className="mt-1">{children}</div>
            </div>
        </li>
    );
}

function FormContacto({ datos }: { datos: RespuestaContacto }) {
    const { antispam, setAntispam, renovar } = useAntispam(datos.antispam, 'contacto');
    const pregunta = antispam?.pregunta ?? null;
    const ids = datos.categorias.map((c) => c.id).join(',');

    // Reglas de ContactoController@store (+ la respuesta de verificación si hay pregunta).
    const f = useFormulario(CONSULTA_VACIA, {
        reglas: {
            nombre: ['requerido', 'min:3', 'max:150'],
            correo: ['requerido', 'email', 'max:150'],
            telefono: ['max:30', REGLA_TELEFONO],
            categoria_consulta_id: ids ? ['requerido', 'entero', `en:${ids}`] : ['requerido', 'entero'],
            mensaje: ['requerido', 'min:10', 'max:3000'],
            ...(pregunta ? { antispam_respuesta: ['requerido', 'entero'] } : {}),
        },
        etiquetas: ETIQUETAS,
    });

    const limpiarRespuesta = () => f.setValores((v) => ({ ...v, antispam_respuesta: '' }));

    const enviar = f.alEnviar(
        async (valores) => {
            try {
                return await api.post<RespuestaEnvio>('contacto', { ...valores, antispam_token: antispam?.token ?? '' });
            } catch (error) {
                revisarErrorAntispam(error, { renovar, limpiarRespuesta });
                throw error;
            }
        },
        {
            onExito: (respuesta) => {
                // Token y pregunta nuevos para poder enviar otra consulta sin recargar la página.
                if (respuesta.data.antispam) {
                    setAntispam(respuesta.data.antispam);
                }

                f.reiniciar();
            },
        },
    );

    const { valores, errores, set, validarUno } = f;
    const control = (campo: keyof typeof CONSULTA_VACIA & string) => ({
        name: campo,
        value: valores[campo],
        onChange: (e: { target: { value: string } }) => set(campo, e.target.value),
        onBlur: () => validarUno(campo),
    });

    return (
        <form className="tarjeta grid gap-5 p-6 md:grid-cols-2" noValidate onSubmit={enviar} aria-labelledby="titulo-consulta">
            <CampoFormulario etiqueta="Nombre completo" requerido error={errores.nombre}>
                <input {...control('nombre')} className="campo" maxLength={150} autoComplete="name" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Correo electrónico" requerido error={errores.correo}>
                <input {...control('correo')} type="email" className="campo" maxLength={150} autoComplete="email" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Teléfono" error={errores.telefono} ayuda="Solo números, espacios y los signos + - ( ).">
                <input {...control('telefono')} type="tel" className="campo" maxLength={30} autoComplete="tel" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Categoría de la consulta" requerido error={errores.categoria_consulta_id}>
                <select {...control('categoria_consulta_id')} className="campo">
                    <option value="">Seleccione...</option>
                    {datos.categorias.map((c) => (
                        <option key={c.id} value={String(c.id)}>
                            {c.nombre}
                        </option>
                    ))}
                </select>
            </CampoFormulario>
            <CampoFormulario
                etiqueta="Mensaje"
                requerido
                error={errores.mensaje}
                ayuda={`Entre 10 y 3000 caracteres (${valores.mensaje.length}/3000).`}
                className="md:col-span-2"
            >
                <textarea {...control('mensaje')} className="campo" rows={6} maxLength={3000} />
            </CampoFormulario>
            <Antispam
                pregunta={pregunta}
                respuesta={valores.antispam_respuesta}
                onRespuesta={(v) => set('antispam_respuesta', v)}
                onSalirRespuesta={() => validarUno('antispam_respuesta')}
                trampa={valores.sitio_web}
                onTrampa={(v) => set('sitio_web', v)}
                error={errores.antispam}
                errorRespuesta={errores.antispam_respuesta}
                className="md:col-span-2"
            />
            <div className="md:col-span-2">
                <button type="submit" className="boton" disabled={f.enviando}>
                    <Send className="size-4" aria-hidden="true" /> {f.enviando ? 'Enviando…' : 'Enviar consulta'}
                </button>
            </div>
        </form>
    );
}
