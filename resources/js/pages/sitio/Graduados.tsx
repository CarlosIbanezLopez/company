import { GraduationCap, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { Link, useNavigate } from 'react-router';
import { Cargando } from '@/components/ui/Cargando';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { Pestanas } from '@/components/ui/Pestanas';
import { Antispam } from '@/components/sitio/Antispam';
import { CamposGraduado } from '@/components/sitio/CamposGraduado';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import { ETIQUETAS_GRADUADO, reglasGraduado, revisarErrorAntispam, useAntispam, valoresGraduado } from '@/components/sitio/formularios';
import type { RespuestaGraduados } from '@/components/sitio/tipos';
import { useAuth } from '@/contexts/AuthContext';
import { useApi } from '@/hooks/useApi';
import { useFormulario } from '@/hooks/useFormulario';
import { useTitulo } from '@/hooks/useTitulo';
import { api } from '@/lib/api';
import { plural } from '@/lib/formato';
import type { Usuario } from '@/types';

/** Programa Alumni: información para graduados y registro de la cuenta con su ficha (RF-11). */
export default function Graduados() {
    useTitulo('Graduados - Alumni');
    const consulta = useApi<RespuestaGraduados>('graduados');

    return (
        <>
            <Encabezado
                titulo="Graduados – Programa Alumni"
                descripcion="Información para graduados, oportunidades de vinculación y seguimiento a titulados."
                migas={[{ titulo: 'Alumni' }]}
            >
                {consulta.datos && (
                    <p className="mt-6 inline-flex items-center gap-2 rounded-lg border border-current/30 px-3 py-1.5 text-sm">
                        <GraduationCap className="size-4" aria-hidden="true" /> {plural(consulta.datos.total, 'graduado registrado', 'graduados registrados')}
                    </p>
                )}
            </Encabezado>

            <BloqueDatos consulta={consulta}>{(datos) => <ContenidoGraduados datos={datos} />}</BloqueDatos>
        </>
    );
}

function ContenidoGraduados({ datos }: { datos: RespuestaGraduados }) {
    const { paginas, tieneFicha } = datos;
    const { usuario, cargando } = useAuth();
    const [pestana, setPestana] = useState(paginas[0]?.slug ?? '');
    const paginaActiva = paginas.find((p) => p.slug === pestana) ?? paginas[0];

    return (
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            {paginas.length > 0 && paginaActiva && (
                <div className="tarjeta mb-12 p-4 sm:p-6">
                    <Pestanas pestanas={paginas.map((p) => ({ id: p.slug, titulo: p.titulo }))} activa={paginaActiva.slug} onCambiar={setPestana}>
                        <div className="contenido" dangerouslySetInnerHTML={{ __html: paginaActiva.html }} />
                    </Pestanas>
                </div>
            )}

            {/* Registro de graduados (RF-11) */}
            <section className="grid gap-8 lg:grid-cols-3" aria-labelledby="titulo-registro">
                <div>
                    <p className="text-xs font-semibold tracking-[0.18em] text-marca uppercase">Seguimiento a titulados</p>
                    <h2 id="titulo-registro" className="mt-1 text-2xl font-bold">
                        Registre sus datos
                    </h2>
                    <p className="mt-3 text-sm text-muted-foreground">
                        Cree su cuenta de graduado para registrar su año de graduación, situación laboral y datos de contacto. Podrá actualizarlos cuando lo necesite
                        ingresando con su correo y contraseña.
                    </p>
                </div>
                <div className="lg:col-span-2">
                    {cargando ? (
                        <Cargando texto="Verificando sesión…" className="py-8" />
                    ) : usuario ? (
                        <div className="rounded-lg border-l-4 border-primary bg-card p-6">
                            <p className="font-semibold">Ya inició sesión como {usuario.name}.</p>
                            <Link to="/graduados/mi-ficha" className="boton mt-4">
                                {tieneFicha || usuario.es_graduado ? 'Actualizar mis datos' : 'Completar mi ficha'}
                            </Link>
                        </div>
                    ) : (
                        <FormRegistro datos={datos} />
                    )}
                </div>
            </section>
        </div>
    );
}

function FormRegistro({ datos }: { datos: RespuestaGraduados }) {
    const { situaciones, modalidades } = datos;
    const { establecerSesion } = useAuth();
    const navigate = useNavigate();
    const { antispam, renovar } = useAntispam(datos.antispam, 'graduados');

    const f = useFormulario(
        { ...valoresGraduado(), email: '', password: '', password_confirmation: '', sitio_web: '' },
        {
            // Reglas de GraduadoController@store (ficha + datos de acceso).
            reglas: {
                ...reglasGraduado(situaciones, modalidades),
                email: ['requerido', 'email', 'max:150'],
                password: ['requerido', 'min:8'],
                password_confirmation: ['requerido', 'igual:password'],
            },
            etiquetas: ETIQUETAS_GRADUADO,
        },
    );

    const registrar = f.alEnviar(
        async (valores) => {
            try {
                return await api.post<{ message: string; token: string; usuario: Usuario }>('graduados', { ...valores, antispam_token: antispam?.token ?? '' });
            } catch (error) {
                revisarErrorAntispam(error, { renovar });
                throw error;
            }
        },
        {
            // El registro deja la sesión iniciada (token Bearer) y lleva a la ficha del graduado.
            onExito: (respuesta) => {
                establecerSesion(respuesta.data.token, respuesta.data.usuario);
                navigate('/graduados/mi-ficha', { replace: true });
            },
        },
    );

    return (
        <form className="tarjeta grid gap-5 p-6 md:grid-cols-2" noValidate onSubmit={registrar} aria-labelledby="titulo-registro">
            <p className="text-sm text-muted-foreground md:col-span-2">Los campos marcados con * son obligatorios.</p>
            <CamposGraduado
                valores={f.valores}
                errores={f.errores}
                onCambiar={(campo, valor) => f.set(campo, valor)}
                onSalir={(campo) => f.validarUno(campo)}
                situaciones={situaciones}
                modalidades={modalidades}
            />

            <div className="border-t pt-5 md:col-span-2">
                <h3 className="text-sm font-semibold">Datos de acceso</h3>
            </div>
            <CampoFormulario etiqueta="Correo electrónico" requerido error={f.errores.email} ayuda="Lo usará para ingresar y actualizar su ficha." className="md:col-span-2">
                <input
                    name="email"
                    type="email"
                    className="campo"
                    autoComplete="email"
                    maxLength={150}
                    value={f.valores.email}
                    onChange={(e) => f.set('email', e.target.value)}
                    onBlur={() => f.validarUno('email')}
                />
            </CampoFormulario>
            <CampoFormulario etiqueta="Contraseña" requerido error={f.errores.password} ayuda="Mínimo 8 caracteres.">
                <input
                    name="password"
                    type="password"
                    className="campo"
                    autoComplete="new-password"
                    value={f.valores.password}
                    onChange={(e) => f.set('password', e.target.value)}
                    onBlur={() => f.validarUno('password')}
                />
            </CampoFormulario>
            <CampoFormulario etiqueta="Confirmar contraseña" requerido error={f.errores.password_confirmation}>
                <input
                    name="password_confirmation"
                    type="password"
                    className="campo"
                    autoComplete="new-password"
                    value={f.valores.password_confirmation}
                    onChange={(e) => f.set('password_confirmation', e.target.value)}
                    onBlur={() => f.validarUno('password_confirmation')}
                />
            </CampoFormulario>

            <Antispam trampa={f.valores.sitio_web} onTrampa={(v) => f.set('sitio_web', v)} error={f.errores.antispam} className="md:col-span-2" />

            <div className="flex flex-wrap items-center gap-4 md:col-span-2">
                <button type="submit" className="boton" disabled={f.enviando}>
                    <UserPlus className="size-4" aria-hidden="true" /> {f.enviando ? 'Registrando…' : 'Registrarme como graduado'}
                </button>
                <Link to="/login" className="text-sm text-primary hover:underline">
                    ¿Ya tiene cuenta? Ingrese aquí
                </Link>
            </div>
        </form>
    );
}
