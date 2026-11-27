import {
    AArrowDown,
    AArrowUp,
    Baby,
    Briefcase,
    Contrast,
    KeyRound,
    LayoutDashboard,
    LoaderCircle,
    LogOut,
    Moon,
    Palette,
    RotateCcw,
    Save,
    Sparkles,
    Sun,
    SunMoon,
    User,
    GraduationCap,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useId, useState } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router';
import { EncabezadoPagina } from '@/components/admin/EncabezadoPagina';
import { Avatar } from '@/components/admin/MenuUsuario';
import { EntradaContrasena } from '@/components/admin/EntradaContrasena';
import { useSalir } from '@/components/admin/useSalir';
import { Interruptor } from '@/components/PanelApariencia';
import { Cargando } from '@/components/ui/Cargando';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { Confirmar } from '@/components/ui/Modal';
import { Pestanas } from '@/components/ui/Pestanas';
import { FUENTE_MAX, FUENTE_MIN, FUENTE_PASO, HORARIO_NOCHE, MODOS, TEMAS, useApariencia } from '@/contexts/AparienciaContext';
import { useAuth } from '@/contexts/AuthContext';
import { useFormulario } from '@/hooks/useFormulario';
import { useTitulo } from '@/hooks/useTitulo';
import { api } from '@/lib/api';
import { fecha } from '@/lib/formato';
import { cn } from '@/lib/utils';
import type { Modo, Tema, Usuario } from '@/types';

/*
 * "Mi cuenta" de cualquier usuario autenticado (en /cuenta dentro del sitio y en /admin/cuenta en el panel):
 * datos personales (PUT /api/perfil), contraseña (PUT /api/perfil/contrasena) y apariencia
 * (tema, modo día/noche, tamaño de letra y contraste: se aplican al instante y se guardan en el perfil).
 */
const SECCIONES = [
    { id: 'perfil', titulo: 'Perfil', icono: User },
    { id: 'contrasena', titulo: 'Contraseña', icono: KeyRound },
    { id: 'apariencia', titulo: 'Apariencia', icono: Palette },
] as const;

type Seccion = (typeof SECCIONES)[number]['id'];

const ICONO_TEMA: Record<Tema, LucideIcon> = { ninos: Baby, jovenes: Sparkles, adultos: Briefcase };
const ICONO_MODO: Record<Modo, LucideIcon> = { auto: SunMoon, dia: Sun, noche: Moon };

function Tarjeta({ titulo, descripcion, children }: { titulo: string; descripcion?: string; children: React.ReactNode }) {
    const id = useId();

    return (
        <section aria-labelledby={id} className="tarjeta p-5 md:p-6">
            <h2 id={id} className="text-lg font-bold">
                {titulo}
            </h2>
            {descripcion && <p className="mt-1 text-sm text-muted-foreground">{descripcion}</p>}
            <div className="mt-5">{children}</div>
        </section>
    );
}

function BotonGuardar({ enviando, texto = 'Guardar cambios' }: { enviando: boolean; texto?: string }) {
    return (
        <button type="submit" className="boton" disabled={enviando}>
            {enviando ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : <Save className="size-4" aria-hidden="true" />}
            {enviando ? 'Guardando…' : texto}
        </button>
    );
}

/* ------------------------------------------------------------------ Perfil */

function FormularioPerfil({ usuario }: { usuario: Usuario }) {
    const { actualizarUsuario } = useAuth();
    const f = useFormulario(
        { name: usuario.name, email: usuario.email, telefono: usuario.telefono ?? '' },
        {
            reglas: { name: ['requerido', 'max:255'], email: ['requerido', 'email', 'max:255'], telefono: ['max:20'] },
            etiquetas: { name: 'nombre', email: 'correo electrónico', telefono: 'teléfono' },
        },
    );

    const alEnviar = f.alEnviar(
        (datos) =>
            api.put<{ message: string; usuario: Usuario }>('perfil', {
                name: datos.name.trim(),
                email: datos.email.trim(),
                telefono: datos.telefono.trim() || null,
            }),
        { onExito: (respuesta) => respuesta.data?.usuario && actualizarUsuario(respuesta.data.usuario) },
    );

    return (
        <Tarjeta titulo="Datos personales" descripcion="Actualice su nombre, su correo electrónico y su teléfono de contacto.">
            <form noValidate onSubmit={alEnviar} className="grid gap-5 md:grid-cols-2">
                <CampoFormulario etiqueta="Nombre completo" error={f.errores.name} requerido className="md:col-span-2">
                    <input name="name" value={f.valores.name} onChange={(e) => f.set('name', e.target.value)} onBlur={() => f.validarUno('name')} autoComplete="name" className="campo" />
                </CampoFormulario>
                <CampoFormulario etiqueta="Correo electrónico" error={f.errores.email} requerido>
                    <input
                        name="email"
                        type="email"
                        value={f.valores.email}
                        onChange={(e) => f.set('email', e.target.value)}
                        onBlur={() => f.validarUno('email')}
                        autoComplete="email"
                        className="campo"
                    />
                </CampoFormulario>
                <CampoFormulario etiqueta="Teléfono" error={f.errores.telefono} ayuda="Opcional. Máximo 20 caracteres.">
                    <input
                        name="telefono"
                        type="tel"
                        value={f.valores.telefono}
                        onChange={(e) => f.set('telefono', e.target.value)}
                        onBlur={() => f.validarUno('telefono')}
                        autoComplete="tel"
                        className="campo"
                    />
                </CampoFormulario>
                <div className="md:col-span-2">
                    <BotonGuardar enviando={f.enviando} />
                </div>
            </form>
        </Tarjeta>
    );
}

/* ------------------------------------------------------------------ Contraseña */

function FormularioContrasena() {
    const f = useFormulario(
        { current_password: '', password: '', password_confirmation: '' },
        {
            reglas: {
                current_password: ['requerido'],
                password: ['requerido', 'min:8', 'max:72', 'confirmado'],
                password_confirmation: ['requerido'],
            },
            etiquetas: { current_password: 'contraseña actual', password: 'nueva contraseña', password_confirmation: 'confirmación de la contraseña' },
        },
    );

    const alEnviar = f.alEnviar((datos) => api.put<{ message: string }>('perfil/contrasena', datos), { onExito: () => f.reiniciar() });

    return (
        <Tarjeta titulo="Cambiar contraseña" descripcion="Use una contraseña larga y difícil de adivinar. Al cambiarla se cierran las sesiones abiertas en otros dispositivos; esta se mantiene.">
            <form noValidate onSubmit={alEnviar} className="grid max-w-xl gap-5">
                <CampoFormulario etiqueta="Contraseña actual" error={f.errores.current_password} requerido>
                    <EntradaContrasena
                        name="current_password"
                        value={f.valores.current_password}
                        onChange={(e) => f.set('current_password', e.target.value)}
                        onBlur={() => f.validarUno('current_password')}
                        autoComplete="current-password"
                    />
                </CampoFormulario>
                <CampoFormulario etiqueta="Nueva contraseña" error={f.errores.password} ayuda="Mínimo 8 caracteres y distinta de la actual." requerido>
                    <EntradaContrasena
                        name="password"
                        value={f.valores.password}
                        onChange={(e) => f.set('password', e.target.value)}
                        onBlur={() => f.validarUno('password')}
                        autoComplete="new-password"
                    />
                </CampoFormulario>
                <CampoFormulario etiqueta="Confirmar nueva contraseña" error={f.errores.password_confirmation} requerido>
                    <EntradaContrasena
                        name="password_confirmation"
                        value={f.valores.password_confirmation}
                        onChange={(e) => f.set('password_confirmation', e.target.value)}
                        onBlur={() => f.validarUno('password_confirmation')}
                        autoComplete="new-password"
                    />
                </CampoFormulario>
                <div>
                    <BotonGuardar enviando={f.enviando} texto="Cambiar contraseña" />
                </div>
            </form>
        </Tarjeta>
    );
}

/* ------------------------------------------------------------------ Apariencia */

function Opcion({ activo, children }: { activo: boolean; children: React.ReactNode }) {
    return (
        <label
            className={cn(
                'flex cursor-pointer flex-col gap-1.5 rounded-lg border-2 p-4 transition-colors has-[:focus-visible]:ring-3 has-[:focus-visible]:ring-ring',
                activo ? 'border-primary bg-primary/5' : 'hover:border-primary/50',
            )}
        >
            {children}
        </label>
    );
}

function PreferenciasApariencia() {
    const a = useApariencia();
    const idFuente = useId();
    const idContraste = useId();

    return (
        <Tarjeta titulo="Apariencia y accesibilidad" descripcion="Los cambios se aplican al instante en todo el portal y se guardan automáticamente en su perfil.">
            <div className="space-y-7">
                <fieldset>
                    <legend className="etiqueta">Tema según el tipo de usuario</legend>
                    <div className="grid gap-3 sm:grid-cols-3">
                        {TEMAS.map((t) => {
                            const IconoTema = ICONO_TEMA[t.valor];

                            return (
                                <Opcion key={t.valor} activo={a.tema === t.valor}>
                                    <input type="radio" name="tema" value={t.valor} checked={a.tema === t.valor} onChange={() => a.setTema(t.valor)} className="sr-only" />
                                    <span className="flex items-center gap-2 font-semibold">
                                        <IconoTema className="size-5 text-primary" aria-hidden="true" /> {t.nombre}
                                    </span>
                                    <span className="text-xs text-muted-foreground">{t.descripcion}</span>
                                </Opcion>
                            );
                        })}
                    </div>
                </fieldset>

                <fieldset>
                    <legend className="etiqueta">Modo día / noche</legend>
                    <div className="grid gap-3 sm:grid-cols-3">
                        {MODOS.map((m) => {
                            const IconoModo = ICONO_MODO[m.valor];

                            return (
                                <Opcion key={m.valor} activo={a.modo === m.valor}>
                                    <input type="radio" name="modo" value={m.valor} checked={a.modo === m.valor} onChange={() => a.setModo(m.valor)} className="sr-only" />
                                    <span className="flex items-center gap-2 font-semibold">
                                        <IconoModo className="size-5 text-primary" aria-hidden="true" /> {m.valor === 'auto' ? 'Automático' : m.nombre}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {m.valor === 'auto'
                                            ? `Noche de ${HORARIO_NOCHE.desde}:00 a ${String(HORARIO_NOCHE.hasta - 1).padStart(2, '0')}:59`
                                            : m.valor === 'dia'
                                              ? 'Fondo claro todo el día'
                                              : 'Fondo oscuro todo el día'}
                                    </span>
                                </Opcion>
                            );
                        })}
                    </div>
                    <p className="mt-2 text-xs text-muted-foreground" aria-live="polite">
                        Ahora se muestra el modo {a.modoEfectivo === 'noche' ? 'noche' : 'día'}
                        {a.modo === 'auto' ? ' (automático según el horario).' : ' (elegido manualmente).'}
                    </p>
                </fieldset>

                <div>
                    <label htmlFor={idFuente} className="etiqueta">
                        Tamaño de letra
                    </label>
                    <div className="flex max-w-xl items-center gap-2">
                        <button type="button" className="boton-icono size-9" onClick={a.reducirFuente} disabled={a.fuente <= FUENTE_MIN} aria-label="Reducir tamaño de letra">
                            <AArrowDown className="size-4" aria-hidden="true" />
                        </button>
                        <input
                            id={idFuente}
                            type="range"
                            min={FUENTE_MIN}
                            max={FUENTE_MAX}
                            step={FUENTE_PASO}
                            value={a.fuente}
                            onChange={(e) => a.setFuente(Number(e.target.value))}
                            aria-valuetext={`${a.fuente} por ciento`}
                            className="h-2 w-full cursor-pointer accent-[var(--primary)]"
                        />
                        <button type="button" className="boton-icono size-9" onClick={a.aumentarFuente} disabled={a.fuente >= FUENTE_MAX} aria-label="Aumentar tamaño de letra">
                            <AArrowUp className="size-4" aria-hidden="true" />
                        </button>
                        <span className="w-12 text-right text-sm font-semibold tabular-nums">{a.fuente}%</span>
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">Vista previa: así se verán los textos del portal.</p>
                </div>

                <div className="flex max-w-xl items-center justify-between gap-4 rounded-lg border p-4">
                    <span id={idContraste} className="flex items-start gap-2 text-sm">
                        <Contrast className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <span>
                            <span className="block font-semibold">Alto contraste</span>
                            <span className="text-xs text-muted-foreground">Colores de máximo contraste y enlaces subrayados (WCAG).</span>
                        </span>
                    </span>
                    <Interruptor activo={a.contraste} onCambiar={a.setContraste} etiquetaId={idContraste} />
                </div>

                <button type="button" className="boton-borde" onClick={a.restablecer}>
                    <RotateCcw className="size-4" aria-hidden="true" /> Restablecer valores predeterminados
                </button>
            </div>
        </Tarjeta>
    );
}

/* ------------------------------------------------------------------ Resumen y sesiones */

function ResumenCuenta({ usuario, enPanel }: { usuario: Usuario; enPanel: boolean }) {
    const salir = useSalir();
    const [confirmar, setConfirmar] = useState(false);
    const [saliendo, setSaliendo] = useState(false);

    const cerrarTodas = async () => {
        setSaliendo(true);
        await salir(true);
    };

    return (
        <div className="tarjeta flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between md:p-6">
            <div className="flex min-w-0 items-center gap-4">
                <Avatar nombre={usuario.name} className="size-14 text-lg" />
                <div className="min-w-0">
                    <p className="truncate text-lg font-bold">{usuario.name}</p>
                    <p className="truncate text-sm text-muted-foreground">{usuario.email}</p>
                    <p className="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {usuario.rol && <span className="insignia rounded-lg bg-secondary text-secondary-foreground">{usuario.rol}</span>}
                        <span>Último acceso: {fecha(usuario.ultimo_acceso, true)}</span>
                        <span>Cuenta creada: {fecha(usuario.created_at)}</span>
                    </p>
                </div>
            </div>
            <div className="flex flex-wrap gap-2">
                {usuario.panel && !enPanel && (
                    <Link to="/admin" className="boton-borde">
                        <LayoutDashboard className="size-4" aria-hidden="true" /> Ir al panel
                    </Link>
                )}
                {usuario.es_graduado && (
                    <Link to="/graduados/mi-ficha" className="boton-borde">
                        <GraduationCap className="size-4" aria-hidden="true" /> Mi ficha de graduado
                    </Link>
                )}
                <button type="button" className="boton-borde" onClick={() => setConfirmar(true)}>
                    <LogOut className="size-4" aria-hidden="true" /> Cerrar todas las sesiones
                </button>
            </div>

            <Confirmar
                abierto={confirmar}
                titulo="¿Cerrar sesión en todos los dispositivos?"
                mensaje="Se cerrarán todas sus sesiones abiertas, incluida esta. Deberá iniciar sesión nuevamente."
                textoConfirmar="Cerrar todas"
                procesando={saliendo}
                onConfirmar={() => void cerrarTodas()}
                onCancelar={() => !saliendo && setConfirmar(false)}
            />
        </div>
    );
}

export default function Cuenta() {
    useTitulo('Mi cuenta');
    const { usuario } = useAuth();
    const { pathname } = useLocation();
    const enPanel = pathname.startsWith('/admin');
    const [params, setParams] = useSearchParams();
    const pedida = params.get('seccion');
    const seccion: Seccion = SECCIONES.some((s) => s.id === pedida) ? (pedida as Seccion) : 'perfil';

    if (!usuario) {
        return <Cargando texto="Cargando su cuenta…" />;
    }

    return (
        <div className={cn('mx-auto w-full max-w-4xl space-y-6', !enPanel && 'px-4 py-8 md:py-12')}>
            <EncabezadoPagina titulo="Mi cuenta" descripcion="Administre sus datos personales, su contraseña y la apariencia del portal." />

            <ResumenCuenta usuario={usuario} enPanel={enPanel} />

            <Pestanas
                pestanas={SECCIONES.map((s) => {
                    const IconoSeccion = s.icono;

                    return {
                        id: s.id,
                        titulo: (
                            <>
                                <IconoSeccion className="size-4" aria-hidden="true" /> {s.titulo}
                            </>
                        ),
                    };
                })}
                activa={seccion}
                onCambiar={(id) => setParams(id === 'perfil' ? {} : { seccion: id }, { replace: true })}
            >
                {seccion === 'perfil' && <FormularioPerfil key={usuario.id} usuario={usuario} />}
                {seccion === 'contrasena' && <FormularioContrasena />}
                {seccion === 'apariencia' && <PreferenciasApariencia />}
            </Pestanas>
        </div>
    );
}
