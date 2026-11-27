import { Briefcase, Send, UserPlus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { Pestanas } from '@/components/ui/Pestanas';
import { Antispam } from '@/components/sitio/Antispam';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import { revisarErrorAntispam, useAntispam } from '@/components/sitio/formularios';
import { Imagen } from '@/components/sitio/Imagen';
import { Seccion } from '@/components/sitio/Seccion';
import type { RespuestaEnvio, RespuestaInnova, ServicioInnova } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useFormulario } from '@/hooks/useFormulario';
import { useTitulo } from '@/hooks/useTitulo';
import { api } from '@/lib/api';
import { fecha } from '@/lib/formato';
import type { Etiquetas, ReglasFormulario } from '@/lib/validacion';

const SOLICITUD_VACIA = { empresa: '', contacto: '', correo: '', telefono: '', innova_servicio_id: '', mensaje: '', sitio_web: '' };
const POSTULACION_VACIA = { nombre: '', registro: '', correo: '', telefono: '', semestre: '', motivacion: '', sitio_web: '' };

type FormularioSolicitud = ReturnType<typeof useFormulario<typeof SOLICITUD_VACIA>>;
type FormularioPostulacion = ReturnType<typeof useFormulario<typeof POSTULACION_VACIA>>;

/** Reglas de InnovaController@solicitar. */
function reglasSolicitud(servicios: ServicioInnova[]): ReglasFormulario {
    return {
        empresa: ['requerido', 'max:150'],
        contacto: ['requerido', 'max:150'],
        correo: ['requerido', 'email', 'max:150'],
        telefono: ['max:30'],
        innova_servicio_id: servicios.length ? ['entero', `en:${servicios.map((s) => s.id).join(',')}`] : [],
        mensaje: ['requerido', 'min:10', 'max:3000'],
    };
}

const ETIQUETAS_SOLICITUD: Etiquetas = {
    empresa: 'empresa',
    contacto: 'persona de contacto',
    correo: 'correo electrónico',
    telefono: 'teléfono',
    innova_servicio_id: 'servicio',
    mensaje: 'mensaje',
};

/** Reglas de InnovaController@postular. */
const REGLAS_POSTULACION: ReglasFormulario = {
    nombre: ['requerido', 'max:150'],
    registro: ['requerido', 'max:20'],
    correo: ['requerido', 'email', 'max:150'],
    telefono: ['max:30'],
    semestre: ['max:30'],
    motivacion: ['requerido', 'min:20', 'max:3000'],
};

const ETIQUETAS_POSTULACION: Etiquetas = {
    nombre: 'nombre completo',
    registro: 'registro universitario',
    correo: 'correo electrónico',
    telefono: 'teléfono',
    semestre: 'semestre',
    motivacion: 'motivación',
};

/** Empresa Junior INNOVA: información, servicios, equipo, proyectos y formularios (RF-10). */
export default function Innova() {
    useTitulo('Empresa Junior INNOVA');
    const consulta = useApi<RespuestaInnova>('innova');

    return (
        <>
            <Encabezado
                titulo="Empresa Junior INNOVA"
                descripcion="Consultoría empresarial realizada por estudiantes de la Carrera de Administración de Empresas."
                migas={[{ titulo: 'Vinculación', url: '/vinculacion' }, { titulo: 'INNOVA' }]}
            />
            <BloqueDatos consulta={consulta}>{(datos) => <ContenidoInnova datos={datos} />}</BloqueDatos>
        </>
    );
}

function ContenidoInnova({ datos }: { datos: RespuestaInnova }) {
    const { descripcion, servicios, integrantes, proyectos } = datos;
    const [pestana, setPestana] = useState('solicitud');
    // Ambos formularios comparten el token antispam de la página; cada envío exitoso entrega uno nuevo.
    const { antispam, setAntispam, renovar } = useAntispam(datos.antispam, 'innova');
    const solicitud = useFormulario(SOLICITUD_VACIA, { reglas: reglasSolicitud(servicios), etiquetas: ETIQUETAS_SOLICITUD });
    const postulacion = useFormulario(POSTULACION_VACIA, { reglas: REGLAS_POSTULACION, etiquetas: ETIQUETAS_POSTULACION });

    const enviarSolicitud = solicitud.alEnviar(
        async (valores) => {
            try {
                return await api.post<RespuestaEnvio>('innova/solicitudes', { ...valores, antispam_token: antispam?.token ?? '' });
            } catch (error) {
                revisarErrorAntispam(error, { renovar });
                throw error;
            }
        },
        {
            onExito: (respuesta) => {
                if (respuesta.data.antispam) {
                    setAntispam(respuesta.data.antispam);
                }

                solicitud.reiniciar();
            },
        },
    );

    const enviarPostulacion = postulacion.alEnviar(
        async (valores) => {
            try {
                return await api.post<RespuestaEnvio>('innova/postulaciones', { ...valores, antispam_token: antispam?.token ?? '' });
            } catch (error) {
                revisarErrorAntispam(error, { renovar });
                throw error;
            }
        },
        {
            onExito: (respuesta) => {
                if (respuesta.data.antispam) {
                    setAntispam(respuesta.data.antispam);
                }

                postulacion.reiniciar();
            },
        },
    );

    return (
        <>
            {descripcion && (
                <Seccion subtitulo="Quiénes somos" titulo={descripcion.titulo}>
                    <div className="contenido max-w-4xl text-lg" dangerouslySetInnerHTML={{ __html: descripcion.html }} />
                </Seccion>
            )}

            <div className="border-y bg-muted/40">
                <Seccion subtitulo="Lo que ofrecemos" titulo="Servicios">
                    {servicios.length > 0 ? (
                        <ol className="grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-2 lg:grid-cols-4">
                            {servicios.map((s, i) => (
                                <li key={s.id} className="bg-card p-6">
                                    <span className="text-sm font-black text-marca" aria-hidden="true">
                                        {String(i + 1).padStart(2, '0')}
                                    </span>
                                    <h3 className="mt-2 text-lg font-bold">{s.nombre}</h3>
                                    {s.descripcion && <p className="mt-2 text-sm text-muted-foreground">{s.descripcion}</p>}
                                </li>
                            ))}
                        </ol>
                    ) : (
                        <p className="text-muted-foreground">Pronto publicaremos nuestros servicios.</p>
                    )}
                </Seccion>
            </div>

            {integrantes.length > 0 && (
                <Seccion subtitulo="Equipo" titulo="Integrantes">
                    <ul className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-5">
                        {integrantes.map((m) => (
                            <li key={m.id} className="overflow-hidden rounded-lg border bg-card">
                                <div className="aspect-square">
                                    <Imagen src={m.foto_url} alt={m.nombre} />
                                </div>
                                <div className="border-t-4 border-primary p-3">
                                    <p className="text-sm font-bold">{m.nombre}</p>
                                    {m.cargo && <p className="text-xs text-marca">{m.cargo}</p>}
                                    {m.semestre && <p className="text-xs text-muted-foreground">{m.semestre}</p>}
                                </div>
                            </li>
                        ))}
                    </ul>
                </Seccion>
            )}

            {proyectos.length > 0 && (
                <Seccion subtitulo="Experiencia" titulo="Proyectos desarrollados">
                    <div className="grid gap-6 md:grid-cols-3">
                        {proyectos.map((p) => (
                            <article key={p.id} className="overflow-hidden rounded-lg border bg-card">
                                <div className="aspect-video">
                                    <Imagen src={p.imagen_url} alt={p.titulo} />
                                </div>
                                <div className="p-5">
                                    <p className="text-xs text-muted-foreground">{[p.cliente, p.fecha ? fecha(p.fecha) : null].filter(Boolean).join(' · ')}</p>
                                    <h3 className="mt-1 font-bold">{p.titulo}</h3>
                                    {p.descripcion && <p className="mt-2 text-sm text-muted-foreground">{p.descripcion}</p>}
                                </div>
                            </article>
                        ))}
                    </div>
                </Seccion>
            )}

            {/* Formularios de solicitud de servicio y postulación */}
            <div className="border-t bg-muted/40">
                <Seccion subtitulo="Formularios" titulo="Trabaje con INNOVA">
                    <Pestanas
                        pestanas={[
                            {
                                id: 'solicitud',
                                titulo: (
                                    <>
                                        <Briefcase className="size-4" aria-hidden="true" /> Soy empresa: solicitar servicio
                                    </>
                                ),
                            },
                            {
                                id: 'postulacion',
                                titulo: (
                                    <>
                                        <UserPlus className="size-4" aria-hidden="true" /> Soy estudiante: postular
                                    </>
                                ),
                            },
                        ]}
                        activa={pestana}
                        onCambiar={setPestana}
                    >
                        {pestana === 'solicitud' ? (
                            <FormSolicitud f={solicitud} servicios={servicios} onEnviar={enviarSolicitud} />
                        ) : (
                            <FormPostulacion f={postulacion} onEnviar={enviarPostulacion} />
                        )}
                    </Pestanas>
                </Seccion>
            </div>
        </>
    );
}

function FormSolicitud({ f, servicios, onEnviar }: { f: FormularioSolicitud; servicios: ServicioInnova[]; onEnviar: (e?: FormEvent) => void }) {
    const { valores, errores, set, validarUno } = f;
    const control = (campo: keyof typeof SOLICITUD_VACIA & string) => ({
        name: campo,
        value: valores[campo],
        onChange: (e: { target: { value: string } }) => set(campo, e.target.value),
        onBlur: () => validarUno(campo),
    });

    return (
        <form className="tarjeta grid gap-5 p-6 md:grid-cols-2" noValidate onSubmit={onEnviar} aria-label="Solicitud de servicio de consultoría">
            <p className="text-sm text-muted-foreground md:col-span-2">Los campos marcados con * son obligatorios.</p>
            <CampoFormulario etiqueta="Empresa" requerido error={errores.empresa}>
                <input {...control('empresa')} className="campo" maxLength={150} autoComplete="organization" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Persona de contacto" requerido error={errores.contacto}>
                <input {...control('contacto')} className="campo" maxLength={150} autoComplete="name" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Correo electrónico" requerido error={errores.correo}>
                <input {...control('correo')} type="email" className="campo" maxLength={150} autoComplete="email" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Teléfono" error={errores.telefono}>
                <input {...control('telefono')} type="tel" className="campo" maxLength={30} autoComplete="tel" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Servicio de interés" error={errores.innova_servicio_id} className="md:col-span-2">
                <select {...control('innova_servicio_id')} className="campo">
                    <option value="">Otro / no estoy seguro</option>
                    {servicios.map((s) => (
                        <option key={s.id} value={String(s.id)}>
                            {s.nombre}
                        </option>
                    ))}
                </select>
            </CampoFormulario>
            <CampoFormulario
                etiqueta="Describa su necesidad"
                requerido
                error={errores.mensaje}
                ayuda={`Entre 10 y 3000 caracteres (${valores.mensaje.length}/3000).`}
                className="md:col-span-2"
            >
                <textarea {...control('mensaje')} className="campo" rows={5} maxLength={3000} />
            </CampoFormulario>
            <Antispam trampa={valores.sitio_web} onTrampa={(v) => set('sitio_web', v)} error={errores.antispam} className="md:col-span-2" />
            <div className="md:col-span-2">
                <button type="submit" className="boton" disabled={f.enviando}>
                    <Send className="size-4" aria-hidden="true" /> {f.enviando ? 'Enviando…' : 'Enviar solicitud'}
                </button>
            </div>
        </form>
    );
}

function FormPostulacion({ f, onEnviar }: { f: FormularioPostulacion; onEnviar: (e?: FormEvent) => void }) {
    const { valores, errores, set, validarUno } = f;
    const control = (campo: keyof typeof POSTULACION_VACIA & string) => ({
        name: campo,
        value: valores[campo],
        onChange: (e: { target: { value: string } }) => set(campo, e.target.value),
        onBlur: () => validarUno(campo),
    });

    return (
        <form className="tarjeta grid gap-5 p-6 md:grid-cols-2" noValidate onSubmit={onEnviar} aria-label="Postulación para integrar INNOVA">
            <p className="text-sm text-muted-foreground md:col-span-2">Los campos marcados con * son obligatorios.</p>
            <CampoFormulario etiqueta="Nombre completo" requerido error={errores.nombre}>
                <input {...control('nombre')} className="campo" maxLength={150} autoComplete="name" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Registro universitario" requerido error={errores.registro}>
                <input {...control('registro')} className="campo" maxLength={20} />
            </CampoFormulario>
            <CampoFormulario etiqueta="Correo electrónico" requerido error={errores.correo}>
                <input {...control('correo')} type="email" className="campo" maxLength={150} autoComplete="email" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Teléfono" error={errores.telefono}>
                <input {...control('telefono')} type="tel" className="campo" maxLength={30} autoComplete="tel" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Semestre que cursa" error={errores.semestre}>
                <input {...control('semestre')} className="campo" placeholder="Ej. 6° semestre" maxLength={30} />
            </CampoFormulario>
            <CampoFormulario
                etiqueta="¿Por qué desea integrar INNOVA?"
                requerido
                error={errores.motivacion}
                ayuda={`Entre 20 y 3000 caracteres (${valores.motivacion.length}/3000).`}
                className="md:col-span-2"
            >
                <textarea {...control('motivacion')} className="campo" rows={5} maxLength={3000} />
            </CampoFormulario>
            <Antispam trampa={valores.sitio_web} onTrampa={(v) => set('sitio_web', v)} error={errores.antispam} className="md:col-span-2" />
            <div className="md:col-span-2">
                <button type="submit" className="boton" disabled={f.enviando}>
                    <Send className="size-4" aria-hidden="true" /> {f.enviando ? 'Enviando…' : 'Enviar postulación'}
                </button>
            </div>
        </form>
    );
}
