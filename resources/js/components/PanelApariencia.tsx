import { AArrowDown, AArrowUp, Baby, Briefcase, Contrast, Moon, Palette, RotateCcw, Sparkles, Sun, SunMoon, X } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { FUENTE_MAX, FUENTE_MIN, FUENTE_PASO, HORARIO_NOCHE, MODOS, TEMAS, useApariencia } from '@/contexts/AparienciaContext';
import { cn } from '@/lib/utils';
import type { Modo, Tema } from '@/types';

const ICONO_TEMA: Record<Tema, typeof Baby> = { ninos: Baby, jovenes: Sparkles, adultos: Briefcase };
const ICONO_MODO: Record<Modo, typeof Sun> = { auto: SunMoon, dia: Sun, noche: Moon };

interface PropsPanel {
    /** Clases del botón que abre el panel (para adaptarlo a la barra donde se coloca). */
    className?: string;
    /** Muestra el texto "Apariencia" junto al ícono. */
    conTexto?: boolean;
    /** Lado hacia el que se despliega el panel. */
    alinear?: 'derecha' | 'izquierda';
}

/**
 * Selector de tema (Niños / Jóvenes / Adultos), modo Día/Noche (automático por horario o manual),
 * tamaño de letra (control deslizante y botones) y alto contraste.
 */
export function PanelApariencia({ className, conTexto = false, alinear = 'derecha' }: PropsPanel) {
    const a = useApariencia();
    const [abierto, setAbierto] = useState(false);
    const contenedor = useRef<HTMLDivElement>(null);
    const id = useId();

    useEffect(() => {
        if (!abierto) {
            return;
        }

        const alClic = (e: MouseEvent) => {
            if (!contenedor.current?.contains(e.target as Node)) {
                setAbierto(false);
            }
        };
        const alTeclear = (e: KeyboardEvent) => e.key === 'Escape' && setAbierto(false);

        document.addEventListener('mousedown', alClic);
        document.addEventListener('keydown', alTeclear);

        return () => {
            document.removeEventListener('mousedown', alClic);
            document.removeEventListener('keydown', alTeclear);
        };
    }, [abierto]);

    return (
        <div ref={contenedor} className="relative">
            <button
                type="button"
                className={cn('inline-flex h-8 items-center justify-center gap-1.5 rounded-md border px-2 text-xs font-medium', className)}
                aria-expanded={abierto}
                aria-controls={`${id}-panel`}
                aria-label="Apariencia y accesibilidad"
                title="Apariencia y accesibilidad"
                onClick={() => setAbierto((v) => !v)}
            >
                <Palette className="size-4" aria-hidden="true" />
                {conTexto && <span>Apariencia</span>}
            </button>

            {abierto && (
                <div
                    id={`${id}-panel`}
                    role="dialog"
                    aria-label="Apariencia y accesibilidad"
                    className={cn(
                        'absolute top-full z-[60] mt-2 w-[min(22rem,calc(100vw-2rem))] rounded-lg border border-t-4 border-t-marca bg-popover p-4 text-popover-foreground shadow-xl',
                        alinear === 'derecha' ? 'right-0' : 'left-0',
                    )}
                >
                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-sm font-bold">Apariencia y accesibilidad</p>
                        <button type="button" className="inline-flex size-7 items-center justify-center rounded-md hover:bg-muted" onClick={() => setAbierto(false)} aria-label="Cerrar">
                            <X className="size-4" aria-hidden="true" />
                        </button>
                    </div>

                    <fieldset>
                        <legend className="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Tema</legend>
                        <div className="grid grid-cols-3 gap-2">
                            {TEMAS.map((t) => {
                                const IconoT = ICONO_TEMA[t.valor];

                                return (
                                    <button
                                        key={t.valor}
                                        type="button"
                                        aria-pressed={a.tema === t.valor}
                                        title={t.descripcion}
                                        onClick={() => a.setTema(t.valor)}
                                        className={cn(
                                            'flex flex-col items-center gap-1 rounded-lg border p-2 text-xs font-medium transition-colors',
                                            a.tema === t.valor ? 'border-primary bg-primary text-primary-foreground' : 'hover:border-primary',
                                        )}
                                    >
                                        <IconoT className="size-5" aria-hidden="true" />
                                        {t.nombre}
                                    </button>
                                );
                            })}
                        </div>
                    </fieldset>

                    <fieldset className="mt-4">
                        <legend className="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Modo día / noche</legend>
                        <div className="grid grid-cols-3 gap-2">
                            {MODOS.map((m) => {
                                const IconoM = ICONO_MODO[m.valor];

                                return (
                                    <button
                                        key={m.valor}
                                        type="button"
                                        aria-pressed={a.modo === m.valor}
                                        onClick={() => a.setModo(m.valor)}
                                        className={cn(
                                            'flex flex-col items-center gap-1 rounded-lg border p-2 text-xs font-medium transition-colors',
                                            a.modo === m.valor ? 'border-primary bg-primary text-primary-foreground' : 'hover:border-primary',
                                        )}
                                    >
                                        <IconoM className="size-5" aria-hidden="true" />
                                        {m.valor === 'auto' ? 'Automático' : m.nombre}
                                    </button>
                                );
                            })}
                        </div>
                        <p className="mt-2 text-xs text-muted-foreground" aria-live="polite">
                            {a.modo === 'auto'
                                ? `Según el horario: noche de ${HORARIO_NOCHE.desde}:00 a ${String(HORARIO_NOCHE.hasta - 1).padStart(2, '0')}:59. Ahora: ${a.modoEfectivo === 'noche' ? 'noche' : 'día'}.`
                                : `Modo ${a.modoEfectivo === 'noche' ? 'noche' : 'día'} fijado manualmente.`}
                        </p>
                    </fieldset>

                    <fieldset className="mt-4">
                        <legend className="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Tamaño de letra</legend>
                        <div className="flex items-center gap-2">
                            <button type="button" className="boton-icono size-9" onClick={a.reducirFuente} disabled={a.fuente <= FUENTE_MIN} aria-label="Reducir tamaño de letra">
                                <AArrowDown className="size-4" aria-hidden="true" />
                            </button>
                            <input
                                type="range"
                                min={FUENTE_MIN}
                                max={FUENTE_MAX}
                                step={FUENTE_PASO}
                                value={a.fuente}
                                onChange={(e) => a.setFuente(Number(e.target.value))}
                                aria-label="Tamaño de letra"
                                aria-valuetext={`${a.fuente} por ciento`}
                                className="h-2 w-full cursor-pointer accent-[var(--primary)]"
                            />
                            <button type="button" className="boton-icono size-9" onClick={a.aumentarFuente} disabled={a.fuente >= FUENTE_MAX} aria-label="Aumentar tamaño de letra">
                                <AArrowUp className="size-4" aria-hidden="true" />
                            </button>
                            <span className="w-12 text-right text-sm font-semibold tabular-nums">{a.fuente}%</span>
                        </div>
                    </fieldset>

                    <div className="mt-4 flex items-center justify-between gap-3">
                        <span id={`${id}-contraste`} className="flex items-center gap-2 text-sm font-medium">
                            <Contrast className="size-4" aria-hidden="true" /> Alto contraste
                        </span>
                        <Interruptor activo={a.contraste} onCambiar={a.setContraste} etiquetaId={`${id}-contraste`} />
                    </div>

                    <button type="button" className="boton-borde mt-4 h-9 w-full" onClick={a.restablecer}>
                        <RotateCcw className="size-4" aria-hidden="true" /> Restablecer valores
                    </button>
                </div>
            )}
        </div>
    );
}

/** Botones rápidos de accesibilidad para barras (A-, A+ y contraste). */
export function BotonesAccesibilidad({ className }: { className?: string }) {
    const a = useApariencia();
    const estilo = cn('inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-1.5 text-xs font-bold', className);

    return (
        <div className="flex items-center gap-1" role="group" aria-label="Accesibilidad">
            <button type="button" className={estilo} onClick={a.reducirFuente} disabled={a.fuente <= FUENTE_MIN} aria-label="Reducir tamaño de letra" title="Reducir tamaño de letra">
                A-
            </button>
            <button type="button" className={estilo} onClick={a.aumentarFuente} disabled={a.fuente >= FUENTE_MAX} aria-label="Aumentar tamaño de letra" title="Aumentar tamaño de letra">
                A+
            </button>
            <button
                type="button"
                className={estilo}
                onClick={a.alternarContraste}
                aria-pressed={a.contraste}
                aria-label="Alto contraste"
                title="Alto contraste"
            >
                <Contrast className="size-4" aria-hidden="true" />
            </button>
            <button
                type="button"
                className={estilo}
                onClick={() => a.setModo(a.modoEfectivo === 'noche' ? 'dia' : 'noche')}
                aria-label={a.modoEfectivo === 'noche' ? 'Cambiar a modo día' : 'Cambiar a modo noche'}
                title={a.modoEfectivo === 'noche' ? 'Cambiar a modo día' : 'Cambiar a modo noche'}
            >
                {a.modoEfectivo === 'noche' ? <Sun className="size-4" aria-hidden="true" /> : <Moon className="size-4" aria-hidden="true" />}
            </button>
        </div>
    );
}

/** Interruptor accesible (role="switch"). */
export function Interruptor({ activo, onCambiar, etiquetaId, etiqueta }: { activo: boolean; onCambiar: (v: boolean) => void; etiquetaId?: string; etiqueta?: string }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={activo}
            aria-labelledby={etiquetaId}
            aria-label={etiquetaId ? undefined : etiqueta}
            onClick={() => onCambiar(!activo)}
            className={cn('relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border-2 transition-colors', activo ? 'border-primary bg-primary' : 'border-input bg-muted')}
        >
            <span className={cn('inline-block size-4 rounded-full bg-background shadow transition-transform', activo ? 'translate-x-5' : 'translate-x-0.5')} />
        </button>
    );
}
