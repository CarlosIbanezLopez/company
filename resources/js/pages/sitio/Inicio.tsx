import { ArrowRight, BookOpen, CalendarDays, ChevronLeft, ChevronRight, ExternalLink, FlaskConical, GraduationCap, Handshake, Library, MapPin, Network, Pause, Play, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { ErrorCarga, Esqueleto } from '@/components/ui/Cargando';
import { EnlaceExterno, EnlaceFlexible, rutaInterna } from '@/components/sitio/Enlace';
import { Imagen } from '@/components/sitio/Imagen';
import { Seccion } from '@/components/sitio/Seccion';
import type { Banner, RespuestaInicio } from '@/components/sitio/tipos';
import { useSitio } from '@/contexts/SitioContext';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha, hora, youtubeEmbed } from '@/lib/formato';
import { cn } from '@/lib/utils';

const ACCESOS: { titulo: string; texto: string; url: string; icono: LucideIcon }[] = [
    { titulo: 'Malla curricular', texto: 'Materias por semestre y prerrequisitos', url: '/malla-curricular', icono: Network },
    { titulo: 'Autoridades y docentes', texto: 'Plantel de la Carrera', url: '/plantel-docente', icono: Users },
    { titulo: 'Investigación', texto: 'Líneas, PIA y semilleros', url: '/investigacion', icono: FlaskConical },
    { titulo: 'Revista INNOVAR', texto: 'Ediciones y artículos', url: '/revista-innovar', icono: BookOpen },
    { titulo: 'Biblioteca digital', texto: 'Reglamentos y formularios', url: '/biblioteca', icono: Library },
    { titulo: 'Vinculación', texto: 'Convenios y empresa junior', url: '/vinculacion', icono: Handshake },
    { titulo: 'Programa Alumni', texto: 'Registro de graduados', url: '/graduados', icono: GraduationCap },
    { titulo: 'Eventos', texto: 'Conferencias y seminarios', url: '/eventos', icono: CalendarDays },
];

const MESES = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
const dia = (f: string) => f.slice(8, 10);
const mes = (f: string) => MESES[Number(f.slice(5, 7)) - 1] ?? '';

/** Página de inicio (RF-01): carrusel, destacados, presentación, noticias, eventos, accesos y plataformas. */
export default function Inicio() {
    useTitulo('Inicio');
    const { sitio } = useSitio();
    const { datos, error, estado, recargar } = useApi<RespuestaInicio>('inicio');

    return (
        <>
            <h1 className="sr-only">Carrera de {sitio.carrera}</h1>
            {error ? <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} /> : !datos ? <CargandoInicio /> : <ContenidoInicio datos={datos} />}
        </>
    );
}

function CargandoInicio() {
    return (
        <div role="status" aria-label="Cargando la página de inicio">
            <Esqueleto className="h-[420px] rounded-none md:h-[520px]" />
            <div className="mx-auto grid max-w-7xl gap-6 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
                {[0, 1, 2, 3].map((i) => (
                    <Esqueleto key={i} className="h-56" />
                ))}
            </div>
        </div>
    );
}

function ContenidoInicio({ datos }: { datos: RespuestaInicio }) {
    const { banners, presentacion, destacados, noticias, eventos, enlaces } = datos;
    const video = presentacion.video ? youtubeEmbed(presentacion.video) : null;

    return (
        <>
            {banners.length > 0 && <Carrusel banners={banners} />}

            {/* Información destacada */}
            {destacados.length > 0 && (
                <section className="border-b bg-card" aria-label="Información destacada">
                    <div className="mx-auto grid max-w-7xl grid-cols-2 px-4 sm:px-6 lg:grid-cols-4">
                        {destacados.map((d) => (
                            <EnlaceFlexible
                                key={d.id}
                                href={d.enlace}
                                className="group border-l py-6 pr-4 pl-5 first:border-l-0 odd:border-l-0 lg:odd:border-l lg:first:border-l-0"
                            >
                                <p className="text-3xl font-black tracking-tight text-primary md:text-4xl">{d.valor}</p>
                                <p className="mt-1 text-sm font-semibold tracking-wide uppercase group-hover:text-marca">{d.titulo}</p>
                                {d.descripcion && <p className="mt-1 text-xs text-muted-foreground">{d.descripcion}</p>}
                            </EnlaceFlexible>
                        ))}
                    </div>
                </section>
            )}

            {/* Mensaje de presentación */}
            <Seccion subtitulo="Presentación" titulo={presentacion.titulo ?? 'Bienvenidos'}>
                <div className="grid gap-10 lg:grid-cols-5">
                    <div className="lg:col-span-3">
                        <div className="contenido text-lg" dangerouslySetInnerHTML={{ __html: presentacion.html }} />
                        {presentacion.autor && (
                            <p className="mt-6 border-l-4 border-marca pl-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase">{presentacion.autor}</p>
                        )}
                    </div>
                    <div className="lg:col-span-2">
                        {video ? (
                            <div className="aspect-video overflow-hidden rounded-lg border bg-muted">
                                <iframe
                                    src={video}
                                    title="Video de presentación de la Carrera"
                                    className="h-full w-full"
                                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowFullScreen
                                    loading="lazy"
                                    referrerPolicy="strict-origin-when-cross-origin"
                                />
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-px overflow-hidden rounded-lg border bg-border">
                                {ACCESOS.slice(0, 4).map((a) => {
                                    const IconoAcceso = a.icono;

                                    return (
                                        <Link key={a.url} to={a.url} className="group bg-card p-5 hover:bg-accent">
                                            <IconoAcceso className="size-6 text-primary" aria-hidden="true" />
                                            <p className="mt-3 text-sm font-semibold group-hover:text-primary">{a.titulo}</p>
                                            <p className="text-xs text-muted-foreground">{a.texto}</p>
                                        </Link>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>
            </Seccion>

            {/* Noticias */}
            <div className="border-y bg-muted/40">
                <Seccion
                    subtitulo="Actualidad"
                    titulo="Noticias y novedades"
                    acciones={
                        <Link to="/noticias" className="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
                            Ver todas <ArrowRight className="size-4" aria-hidden="true" />
                        </Link>
                    }
                >
                    {noticias.length > 0 ? (
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {noticias.map((n, i) => (
                                <Link
                                    key={n.id}
                                    to={`/noticias/${n.slug}`}
                                    className={cn(
                                        'group flex flex-col overflow-hidden rounded-lg border bg-card transition-colors hover:border-primary',
                                        i === 0 && 'lg:col-span-2 lg:row-span-2',
                                    )}
                                >
                                    <div className={cn('overflow-hidden', i === 0 ? 'aspect-[16/10]' : 'aspect-video')}>
                                        <Imagen src={n.imagen_url} alt={n.titulo} className="transition-transform duration-500 group-hover:scale-105" />
                                    </div>
                                    <div className="flex flex-1 flex-col p-5">
                                        <div className="flex flex-wrap items-center gap-2 text-xs">
                                            {n.categoria && <span className="rounded-lg bg-primary px-2 py-0.5 font-semibold text-primary-foreground">{n.categoria.nombre}</span>}
                                            <span className="text-muted-foreground">{fecha(n.fecha_publicacion)}</span>
                                        </div>
                                        <h3 className={cn('mt-3 leading-snug font-bold group-hover:text-primary', i === 0 ? 'text-2xl' : 'text-base')}>{n.titulo}</h3>
                                        {n.resumen && i === 0 && <p className="mt-3 text-sm text-muted-foreground">{n.resumen}</p>}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    ) : (
                        <p className="text-muted-foreground">Aún no hay noticias publicadas.</p>
                    )}
                </Seccion>
            </div>

            {/* Eventos y accesos */}
            <Seccion>
                <div className="grid gap-12 lg:grid-cols-5">
                    <section className="lg:col-span-2" aria-labelledby="inicio-eventos">
                        <div className="mb-6 flex items-end justify-between border-b pb-4">
                            <div>
                                <p className="text-xs font-semibold tracking-[0.18em] text-marca uppercase">Agenda</p>
                                <h2 id="inicio-eventos" className="mt-1 text-2xl font-bold">
                                    Próximos eventos
                                </h2>
                            </div>
                            <Link to="/eventos" className="text-sm font-semibold text-primary hover:underline">
                                Ver agenda
                            </Link>
                        </div>
                        {eventos.length > 0 ? (
                            <ul className="divide-y overflow-hidden rounded-lg border">
                                {eventos.map((e) => (
                                    <li key={e.id}>
                                        <Link to={`/eventos/${e.slug}`} className="group flex gap-4 bg-card p-4 hover:bg-accent">
                                            <div className="flex w-16 shrink-0 flex-col items-center justify-center rounded-lg bg-primary py-2 text-primary-foreground">
                                                <span className="text-2xl leading-none font-black">{dia(e.fecha)}</span>
                                                <span className="text-[11px] font-semibold tracking-widest">{mes(e.fecha)}</span>
                                            </div>
                                            <div className="min-w-0">
                                                <p className="text-xs font-semibold tracking-wide text-marca uppercase">{datos.tipos?.[e.tipo] ?? e.tipo}</p>
                                                <p className="leading-snug font-semibold group-hover:text-primary">{e.titulo}</p>
                                                <p className="mt-1 flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                                                    {e.lugar && (
                                                        <>
                                                            <MapPin className="size-3" aria-hidden="true" /> {e.lugar}
                                                        </>
                                                    )}
                                                    {e.hora_inicio && <span>· {hora(e.hora_inicio)}</span>}
                                                </p>
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="rounded-lg border bg-card p-6 text-sm text-muted-foreground">No hay eventos programados por el momento.</p>
                        )}
                    </section>

                    <section className="lg:col-span-3" aria-labelledby="inicio-accesos">
                        <div className="mb-6 border-b pb-4">
                            <p className="text-xs font-semibold tracking-[0.18em] text-marca uppercase">Accesos rápidos</p>
                            <h2 id="inicio-accesos" className="mt-1 text-2xl font-bold">
                                Explora la Carrera
                            </h2>
                        </div>
                        <div className="grid grid-cols-2 gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-4">
                            {ACCESOS.map((a) => {
                                const IconoAcceso = a.icono;

                                return (
                                    <Link
                                        key={a.url}
                                        to={a.url}
                                        className="group flex flex-col bg-card p-5 transition-colors hover:bg-primary hover:text-primary-foreground"
                                    >
                                        <IconoAcceso className="size-6 text-primary group-hover:text-primary-foreground" aria-hidden="true" />
                                        <p className="mt-4 text-sm font-semibold">{a.titulo}</p>
                                        <p className="mt-1 text-xs text-muted-foreground group-hover:text-primary-foreground">{a.texto}</p>
                                    </Link>
                                );
                            })}
                        </div>
                    </section>
                </div>
            </Seccion>

            {/* Enlaces a plataformas de la Universidad */}
            {enlaces.length > 0 && (
                <section className="border-t bg-card" aria-labelledby="inicio-plataformas">
                    <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                        <h2 id="inicio-plataformas" className="mb-5 text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase">
                            Plataformas de la Universidad
                        </h2>
                        <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {enlaces.map((e) => (
                                <li key={e.id}>
                                    <EnlaceExterno href={e.url} className="group flex h-full items-start justify-between gap-3 rounded-lg border p-4 hover:border-primary">
                                        <span>
                                            <span className="block text-sm font-semibold group-hover:text-primary">{e.nombre}</span>
                                            {e.descripcion && <span className="mt-1 block text-xs text-muted-foreground">{e.descripcion}</span>}
                                        </span>
                                        <ExternalLink className="size-4 shrink-0 text-muted-foreground group-hover:text-primary" aria-hidden="true" />
                                    </EnlaceExterno>
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>
            )}
        </>
    );
}

/** El usuario pidió reducir el movimiento: el carrusel no avanza solo. */
function prefiereMenosMovimiento(): boolean {
    return typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true;
}

/**
 * Carrusel principal accesible: avanza solo cada 6,5 s, se detiene al pasar el mouse o enfocar un control,
 * tiene botón de pausa (WCAG 2.2.2) y respeta la preferencia de movimiento reducido.
 */
function Carrusel({ banners }: { banners: Banner[] }) {
    const total = banners.length;
    const [actual, setActual] = useState(0);
    const [pausado, setPausado] = useState(prefiereMenosMovimiento);
    const [detenido, setDetenido] = useState(false);
    const [vuelta, setVuelta] = useState(0);
    const automatico = total > 1 && !pausado && !detenido;

    useEffect(() => {
        if (!automatico) {
            return;
        }

        const temporizador = window.setInterval(() => setActual((a) => (a + 1) % total), 6500);

        return () => window.clearInterval(temporizador);
    }, [automatico, total, vuelta]);

    const ir = (i: number) => {
        setActual(((i % total) + total) % total);
        setVuelta((v) => v + 1);
    };

    const botonControl = 'inline-flex size-10 items-center justify-center rounded-lg border border-franja-foreground/40 text-franja-foreground hover:bg-franja-foreground/10';

    return (
        <section
            className="relative overflow-hidden bg-primary"
            aria-roledescription="carrusel"
            aria-label="Avisos destacados"
            onMouseEnter={() => setDetenido(true)}
            onMouseLeave={() => setDetenido(false)}
            onFocus={() => setDetenido(true)}
            onBlur={(e) => !e.currentTarget.contains(e.relatedTarget as Node | null) && setDetenido(false)}
        >
            <div className="relative h-[420px] md:h-[520px]" aria-live={automatico ? 'off' : 'polite'}>
                {banners.map((banner, i) => {
                    const visible = i === actual;
                    const interna = banner.enlace ? rutaInterna(banner.enlace) : null;

                    return (
                        <div
                            key={banner.id}
                            role="group"
                            aria-roledescription="diapositiva"
                            aria-label={`${i + 1} de ${total}: ${banner.titulo}`}
                            aria-hidden={!visible}
                            inert={!visible}
                            className={cn('absolute inset-0 transition-opacity duration-700', visible ? 'opacity-100' : 'pointer-events-none opacity-0')}
                        >
                            <Imagen src={banner.imagen_url} alt={banner.titulo} />
                            <div className="absolute inset-0 bg-linear-to-r from-franja/90 via-franja/60 to-transparent" aria-hidden="true" />
                            <div className="absolute inset-0 mx-auto flex max-w-7xl flex-col justify-center px-4 sm:px-6">
                                <div className="max-w-2xl text-franja-foreground">
                                    <span className="mb-4 block h-1 w-16 bg-marca" aria-hidden="true" />
                                    <h2 className="text-3xl font-bold tracking-tight text-balance md:text-5xl">{banner.titulo}</h2>
                                    {banner.subtitulo && <p className="mt-4 text-lg md:text-xl">{banner.subtitulo}</p>}
                                    {banner.enlace && banner.texto_boton && interna && (
                                        <Link
                                            to={interna}
                                            className="mt-8 inline-flex items-center gap-2 rounded-lg bg-marca px-6 py-3 text-sm font-semibold text-marca-foreground hover:opacity-90"
                                        >
                                            {banner.texto_boton} <ArrowRight className="size-4" aria-hidden="true" />
                                        </Link>
                                    )}
                                    {banner.enlace && banner.texto_boton && !interna && (
                                        <EnlaceExterno
                                            href={banner.enlace}
                                            className="mt-8 inline-flex items-center gap-2 rounded-lg bg-marca px-6 py-3 text-sm font-semibold text-marca-foreground hover:opacity-90"
                                        >
                                            {banner.texto_boton} <ExternalLink className="size-4" aria-hidden="true" />
                                        </EnlaceExterno>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {total > 1 && (
                <div className="absolute inset-x-0 bottom-0">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 pb-6 sm:px-6">
                        <div className="flex flex-wrap gap-2">
                            {banners.map((b, i) => (
                                <button
                                    key={b.id}
                                    type="button"
                                    className={cn('h-2 transition-all', i === actual ? 'w-10 bg-marca' : 'w-5 bg-franja-foreground/60 hover:bg-franja-foreground')}
                                    aria-label={`Ir al aviso ${i + 1}`}
                                    aria-current={i === actual ? 'true' : undefined}
                                    onClick={() => ir(i)}
                                />
                            ))}
                        </div>
                        <div className="flex gap-1">
                            <button
                                type="button"
                                className={botonControl}
                                aria-label={pausado ? 'Reanudar el carrusel' : 'Pausar el carrusel'}
                                onClick={() => setPausado((p) => !p)}
                            >
                                {pausado ? <Play className="size-4" aria-hidden="true" /> : <Pause className="size-4" aria-hidden="true" />}
                            </button>
                            <button type="button" className={botonControl} aria-label="Aviso anterior" onClick={() => ir(actual - 1)}>
                                <ChevronLeft className="size-5" aria-hidden="true" />
                            </button>
                            <button type="button" className={botonControl} aria-label="Aviso siguiente" onClick={() => ir(actual + 1)}>
                                <ChevronRight className="size-5" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </section>
    );
}
