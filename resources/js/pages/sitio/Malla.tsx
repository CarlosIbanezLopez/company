import { ArrowDownRight, ArrowUpLeft, Clock, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useSearchParams } from 'react-router';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import type { Materia, RespuestaMalla } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { cn } from '@/lib/utils';

type Estado = 'normal' | 'actual' | 'previa' | 'posterior' | 'atenuada';

const CLASES: Record<Estado, string> = {
    normal: 'border-border bg-card hover:border-primary',
    actual: 'border-primary bg-primary text-primary-foreground',
    previa: 'border-marca bg-marca/10 ring-2 ring-marca',
    posterior: 'border-chart-5 bg-chart-5/15 ring-2 ring-chart-5',
    atenuada: 'border-border bg-card opacity-35',
};

const AVISO_ESTADO: Partial<Record<Estado, string>> = {
    previa: ' (prerrequisito de la materia seleccionada)',
    posterior: ' (depende de la materia seleccionada)',
};

const TIPOS_MATERIA: Record<string, string> = {
    obligatoria: 'Obligatoria',
    electiva: 'Electiva',
    modalidad: 'Modalidad de graduación',
};

/** Malla curricular interactiva por semestre con prerrequisitos y materias posteriores (RF-04). */
export default function Malla() {
    useTitulo('Malla curricular');
    const consulta = useApi<RespuestaMalla>('malla-curricular');
    const [params] = useSearchParams();
    // /malla-curricular?materia=15 (enlace del buscador) abre directamente esa materia.
    const materiaUrl = Number(params.get('materia')) || null;
    const datos = consulta.datos;
    const totalCreditos = datos?.materias.reduce((suma, m) => suma + Number(m.creditos || 0), 0) ?? 0;

    return (
        <>
            <Encabezado
                titulo="Malla curricular interactiva"
                descripcion="Seleccione una materia para ver sus datos, los prerrequisitos que necesita y las materias que dependen de ella."
                migas={[{ titulo: 'Académica' }, { titulo: 'Malla curricular' }]}
            >
                {datos && (
                    <div className="mt-6 flex flex-wrap gap-6 text-sm">
                        <span>
                            <strong className="text-2xl">{datos.semestres.length}</strong> semestres
                        </span>
                        <span>
                            <strong className="text-2xl">{datos.materias.length}</strong> materias
                        </span>
                        <span>
                            <strong className="text-2xl">{totalCreditos}</strong> créditos
                        </span>
                    </div>
                )}
            </Encabezado>

            <BloqueDatos consulta={consulta}>{(d) => <MallaInteractiva datos={d} materiaUrl={materiaUrl} />}</BloqueDatos>
        </>
    );
}

function MallaInteractiva({ datos, materiaUrl }: { datos: RespuestaMalla; materiaUrl: number | null }) {
    const { materias, semestres } = datos;
    const porId = useMemo(() => new Map(materias.map((m) => [m.id, m])), [materias]);
    const [seleccionadaId, setSeleccionadaId] = useState<number | null>(() => (materiaUrl && porId.has(materiaUrl) ? materiaUrl : null));
    const [urlPrevia, setUrlPrevia] = useState(materiaUrl);
    const botones = useRef(new Map<number, HTMLButtonElement>());

    // Si se llega desde el buscador estando ya en la malla, se selecciona la materia indicada.
    if (materiaUrl !== urlPrevia) {
        setUrlPrevia(materiaUrl);

        if (materiaUrl && porId.has(materiaUrl)) {
            setSeleccionadaId(materiaUrl);
        }
    }

    // Lleva a la vista la materia indicada en la URL (la malla se desplaza horizontalmente).
    useEffect(() => {
        if (materiaUrl) {
            botones.current.get(materiaUrl)?.scrollIntoView({ block: 'center', inline: 'center', behavior: 'smooth' });
        }
    }, [materiaUrl]);

    const seleccionada = seleccionadaId !== null ? (porId.get(seleccionadaId) ?? null) : null;
    const previas = new Set(seleccionada?.prerequisitos ?? []);
    const posteriores = new Set(seleccionada?.posteriores ?? []);

    const estado = (m: Materia): Estado => {
        if (!seleccionada) return 'normal';
        if (m.id === seleccionada.id) return 'actual';
        if (previas.has(m.id)) return 'previa';
        if (posteriores.has(m.id)) return 'posterior';

        return 'atenuada';
    };

    const seleccionar = (id: number) => setSeleccionadaId((actual) => (actual === id ? null : id));

    const ListaMaterias = ({ ids, vacio }: { ids: number[]; vacio: string }) =>
        ids.length > 0 ? (
            <ul className="space-y-1 text-sm">
                {ids.map((id) => {
                    const m = porId.get(id);

                    return m ? (
                        <li key={id}>
                            <button type="button" className="text-left hover:text-primary hover:underline" onClick={() => setSeleccionadaId(id)}>
                                {m.sigla} - {m.nombre}
                            </button>
                        </li>
                    ) : null;
                })}
            </ul>
        ) : (
            <p className="text-sm text-muted-foreground">{vacio}</p>
        );

    return (
        <div className="mx-auto max-w-[1500px] px-4 py-8 sm:px-6">
            <div className="mb-5 flex flex-wrap items-center gap-4 text-xs" aria-label="Leyenda de colores">
                <span className="inline-flex items-center gap-2">
                    <span className="size-3.5 bg-primary" aria-hidden="true" /> Materia seleccionada
                </span>
                <span className="inline-flex items-center gap-2">
                    <span className="size-3.5 border-2 border-marca bg-marca/10" aria-hidden="true" /> Prerrequisito
                </span>
                <span className="inline-flex items-center gap-2">
                    <span className="size-3.5 border-2 border-chart-5 bg-chart-5/15" aria-hidden="true" /> Materia posterior que depende de ella
                </span>
            </div>

            {materias.length === 0 ? (
                <p className="rounded-lg border bg-card p-8 text-center text-muted-foreground">Aún no se registraron materias en la malla curricular.</p>
            ) : (
                <div className="overflow-x-auto pb-4">
                    <div className="grid min-w-[1100px] gap-3" style={{ gridTemplateColumns: `repeat(${Math.max(semestres.length, 1)}, minmax(0, 1fr))` }}>
                        {semestres.map((s) => (
                            <section key={s} className="flex flex-col gap-2" aria-label={`${s}° semestre`}>
                                <h2 className="rounded-lg bg-primary px-2 py-2 text-center text-xs font-bold tracking-wider text-primary-foreground uppercase dark:bg-secondary dark:text-foreground">
                                    {s}° semestre
                                </h2>
                                {materias
                                    .filter((m) => m.semestre === s)
                                    .map((m) => {
                                        const e = estado(m);

                                        return (
                                            <button
                                                key={m.id}
                                                ref={(el) => {
                                                    if (el) {
                                                        botones.current.set(m.id, el);
                                                    } else {
                                                        botones.current.delete(m.id);
                                                    }
                                                }}
                                                type="button"
                                                className={cn('rounded-lg border-2 p-2 text-left transition-all', CLASES[e])}
                                                aria-pressed={seleccionada?.id === m.id}
                                                onClick={() => seleccionar(m.id)}
                                            >
                                                <span className="block text-[11px] font-bold tracking-wide opacity-80">{m.sigla}</span>
                                                <span className="mt-0.5 block text-xs leading-snug font-semibold">{m.nombre}</span>
                                                <span className="mt-1 block text-[10px] opacity-70">{m.creditos} créditos</span>
                                                {AVISO_ESTADO[e] && <span className="sr-only">{AVISO_ESTADO[e]}</span>}
                                            </button>
                                        );
                                    })}
                            </section>
                        ))}
                    </div>
                </div>
            )}

            {/* Detalle de la materia seleccionada */}
            {seleccionada && (
                <section
                    className="sticky bottom-0 z-30 mt-4 overflow-hidden rounded-lg border-2 border-primary bg-card shadow-2xl"
                    aria-label="Detalle de la materia seleccionada"
                    aria-live="polite"
                >
                    <div className="flex items-start justify-between gap-4 border-b bg-muted px-5 py-3">
                        <div>
                            <p className="text-xs font-bold tracking-wider text-marca uppercase">
                                {seleccionada.sigla} · {seleccionada.semestre}° semestre · {TIPOS_MATERIA[seleccionada.tipo] ?? seleccionada.tipo}
                            </p>
                            <h2 className="text-xl font-bold">{seleccionada.nombre}</h2>
                        </div>
                        <button type="button" className="boton-icono size-9" aria-label="Cerrar el detalle de la materia" onClick={() => setSeleccionadaId(null)}>
                            <X className="size-4" aria-hidden="true" />
                        </button>
                    </div>
                    <div className="grid gap-6 p-5 md:grid-cols-3">
                        <div>
                            {seleccionada.descripcion && <p className="text-sm text-muted-foreground">{seleccionada.descripcion}</p>}
                            <p className="mt-3 flex items-center gap-2 text-sm">
                                <Clock className="size-4 shrink-0" aria-hidden="true" /> {seleccionada.horas_teoricas} h teóricas · {seleccionada.horas_practicas} h prácticas ·{' '}
                                {seleccionada.creditos} créditos
                            </p>
                        </div>
                        <div>
                            <h3 className="mb-2 flex items-center gap-2 text-xs font-bold tracking-wider text-marca uppercase">
                                <ArrowUpLeft className="size-4" aria-hidden="true" /> Prerrequisitos
                            </h3>
                            <ListaMaterias ids={seleccionada.prerequisitos} vacio="No tiene prerrequisitos." />
                        </div>
                        <div>
                            <h3 className="mb-2 flex items-center gap-2 text-xs font-bold tracking-wider text-chart-5 uppercase">
                                <ArrowDownRight className="size-4" aria-hidden="true" /> Habilita a
                            </h3>
                            <ListaMaterias ids={seleccionada.posteriores} vacio="Ninguna materia depende de esta." />
                        </div>
                    </div>
                </section>
            )}
        </div>
    );
}
