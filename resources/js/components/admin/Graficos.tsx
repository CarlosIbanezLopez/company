import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, LabelList, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import type { TooltipContentProps, YAxisTickContentProps } from 'recharts';
import { useApariencia } from '@/contexts/AparienciaContext';
import { numero } from '@/lib/formato';
import { cn } from '@/lib/utils';

/*
 * Gráficos del panel (recharts) con los tokens del tema: series con var(--chart-1..5) (o tokens de estado
 * cuando el color significa éxito/error) y ejes, cuadrícula y tooltip con tokens de texto y borde,
 * de modo que funcionan en los tres temas, en modo noche y en alto contraste.
 * Convenciones: líneas de 2 px, áreas al 10 %, barras finas (máx. 24 px) separadas por 2 px,
 * cuadrícula sólida y tenue, leyenda solo con dos o más series y tabla de datos equivalente.
 */
export interface SerieGrafico {
    clave: string;
    nombre: string;
    /** Color como token CSS, p. ej. "var(--chart-1)". */
    color: string;
    icono?: LucideIcon;
}

export type FilaGrafico = Record<string, string | number | null | undefined>;

type Formato = (valor: string | number) => string;
type FormatoEtiqueta = (valor: string | number) => ReactNode;

const compacto = new Intl.NumberFormat('es-BO', { notation: 'compact', maximumFractionDigits: 1 });

/** Marcas del eje Y: completas por debajo de 10 000 y compactas por encima (12 mil). */
export const formatoEje: Formato = (valor) => {
    const n = Number(valor);

    return Math.abs(n) >= 10000 ? compacto.format(n) : numero(n);
};

const TICK = { fill: 'var(--muted-foreground)', fontSize: 12 };
const EJE = { stroke: 'var(--border)' };

/** En el tema Adultos las esquinas son rectas; en Jóvenes y Niños, redondeadas (4 px). */
function useRadio(): number {
    return useApariencia().tema === 'adultos' ? 0 : 4;
}

interface ExtraTooltip {
    formatoEtiqueta?: FormatoEtiqueta;
    unidad?: string;
}

/** Tooltip con tokens: el valor destaca y la serie se identifica con una línea de su color. */
function ContenidoTooltip({ active, payload, label, formatoEtiqueta, unidad }: TooltipContentProps & ExtraTooltip) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="rounded-lg border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-lg">
            {label !== undefined && label !== '' && <p className="mb-1.5 font-medium text-muted-foreground">{formatoEtiqueta ? formatoEtiqueta(label) : label}</p>}
            <ul className="space-y-1">
                {payload.map((p, i) => (
                    <li key={`${String(p.dataKey ?? p.name)}-${i}`} className="flex items-center gap-2">
                        <span aria-hidden="true" className="h-0.5 w-3 shrink-0 rounded-full" style={{ backgroundColor: p.color ?? p.fill ?? p.stroke }} />
                        <strong className="text-sm font-semibold text-popover-foreground tabular-nums">
                            {numero(Number(p.value ?? 0))}
                            {unidad ? ` ${unidad}` : ''}
                        </strong>
                        {p.name !== undefined && <span className="text-muted-foreground">{p.name}</span>}
                    </li>
                ))}
            </ul>
        </div>
    );
}

/** Leyenda en HTML (muestra y nombre de cada serie); solo con dos o más series. */
export function LeyendaGrafico({ series, className }: { series: SerieGrafico[]; className?: string }) {
    if (series.length < 2) {
        return null;
    }

    return (
        <ul className={cn('flex flex-wrap items-center gap-x-4 gap-y-1 text-xs', className)} aria-label="Leyenda del gráfico">
            {series.map((s) => {
                const IconoSerie = s.icono;

                return (
                    <li key={s.clave} className="inline-flex items-center gap-1.5">
                        <span aria-hidden="true" className="size-2.5 rounded-sm" style={{ backgroundColor: s.color }} />
                        {IconoSerie && <IconoSerie className="size-3.5 text-muted-foreground" aria-hidden="true" />}
                        <span>{s.nombre}</span>
                    </li>
                );
            })}
        </ul>
    );
}

interface ColumnaTabla {
    clave: string;
    etiqueta: string;
    numerica?: boolean;
    formato?: (valor: unknown, fila: FilaGrafico) => ReactNode;
}

/** Tabla equivalente del gráfico (accesible sin depender del color ni del cursor). */
export function TablaDatos({ columnas, filas, resumen = 'Ver datos en tabla' }: { columnas: ColumnaTabla[]; filas: FilaGrafico[]; resumen?: string }) {
    if (!filas.length) {
        return null;
    }

    return (
        <details className="mt-4 text-sm print:hidden">
            <summary className="cursor-pointer text-primary hover:underline">{resumen}</summary>
            <div className="mt-2 max-h-64 overflow-auto rounded-lg border">
                <table className="w-full">
                    <thead className="sticky top-0 bg-muted text-left text-xs text-muted-foreground uppercase">
                        <tr>
                            {columnas.map((c) => (
                                <th key={c.clave} scope="col" className={cn('px-3 py-2 font-semibold', c.numerica && 'text-right')}>
                                    {c.etiqueta}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {filas.map((f, i) => (
                            <tr key={i}>
                                {columnas.map((c) => (
                                    <td key={c.clave} className={cn('px-3 py-1.5', c.numerica && 'text-right tabular-nums')}>
                                        {c.formato ? c.formato(f[c.clave], f) : c.numerica ? numero(Number(f[c.clave] ?? 0)) : String(f[c.clave] ?? '—')}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </details>
    );
}

interface PropsSerieTiempo {
    datos: FilaGrafico[];
    /** Clave del eje X (p. ej. "fecha"). */
    claveX: string;
    series: SerieGrafico[];
    /** Nombre accesible del gráfico. */
    etiqueta: string;
    alto?: number;
    formatoX?: Formato;
    formatoEtiqueta?: FormatoEtiqueta;
    unidad?: string;
}

/** Serie temporal en área (línea de 2 px con relleno al 10 %), con cursor vertical y tooltip. */
export function GraficoArea({ datos, claveX, series, etiqueta, alto = 260, formatoX, formatoEtiqueta, unidad }: PropsSerieTiempo) {
    return (
        <figure aria-label={etiqueta} className="m-0 w-full min-w-0" style={{ height: alto }}>
            <ResponsiveContainer width="100%" height="100%">
                <AreaChart data={datos} margin={{ top: 8, right: 12, bottom: 0, left: 0 }}>
                    <CartesianGrid vertical={false} stroke="var(--border)" />
                    <XAxis dataKey={claveX} tickFormatter={formatoX} tick={TICK} tickLine={false} axisLine={EJE} minTickGap={20} tickMargin={8} />
                    <YAxis allowDecimals={false} tickFormatter={formatoEje} tick={TICK} tickLine={false} axisLine={false} width={44} />
                    <Tooltip
                        cursor={{ stroke: 'var(--muted-foreground)', strokeWidth: 1 }}
                        content={(p) => <ContenidoTooltip {...p} formatoEtiqueta={formatoEtiqueta} unidad={unidad} />}
                    />
                    {series.map((s) => (
                        <Area
                            key={s.clave}
                            type="monotone"
                            dataKey={s.clave}
                            name={s.nombre}
                            stroke={s.color}
                            strokeWidth={2}
                            fill={s.color}
                            fillOpacity={0.1}
                            dot={false}
                            activeDot={{ r: 4, fill: s.color, stroke: 'var(--card)', strokeWidth: 2 }}
                        />
                    ))}
                </AreaChart>
            </ResponsiveContainer>
        </figure>
    );
}

interface PropsColumnas extends PropsSerieTiempo {
    /** Apila las series en lugar de agruparlas. */
    apilado?: boolean;
}

/** Columnas verticales (una o varias series agrupadas, separadas por 2 px). */
export function GraficoColumnas({ datos, claveX, series, etiqueta, alto = 260, formatoX, formatoEtiqueta, unidad, apilado = false }: PropsColumnas) {
    const r = useRadio();
    const arriba: [number, number, number, number] = [r, r, 0, 0];
    const recto: [number, number, number, number] = [0, 0, 0, 0];

    return (
        <figure aria-label={etiqueta} className="m-0 w-full min-w-0" style={{ height: alto }}>
            <ResponsiveContainer width="100%" height="100%">
                <BarChart data={datos} margin={{ top: 8, right: 12, bottom: 0, left: 0 }} barGap={2} barCategoryGap="18%">
                    <CartesianGrid vertical={false} stroke="var(--border)" />
                    <XAxis dataKey={claveX} tickFormatter={formatoX} tick={TICK} tickLine={false} axisLine={EJE} minTickGap={12} tickMargin={8} />
                    <YAxis allowDecimals={false} tickFormatter={formatoEje} tick={TICK} tickLine={false} axisLine={false} width={44} />
                    <Tooltip
                        cursor={{ fill: 'var(--muted)', fillOpacity: 0.7 }}
                        content={(p) => <ContenidoTooltip {...p} formatoEtiqueta={formatoEtiqueta} unidad={unidad} />}
                    />
                    {series.map((s, i) => (
                        <Bar
                            key={s.clave}
                            dataKey={s.clave}
                            name={s.nombre}
                            fill={s.color}
                            maxBarSize={24}
                            stackId={apilado ? 'pila' : undefined}
                            radius={!apilado || i === series.length - 1 ? arriba : recto}
                        />
                    ))}
                </BarChart>
            </ResponsiveContainer>
        </figure>
    );
}

/** Etiqueta del eje de categorías recortada al ancho disponible (el texto completo queda en <title>). */
function EtiquetaCategoria({ x, y, payload, ancho }: YAxisTickContentProps & { ancho: number }) {
    const texto = String(payload?.value ?? '');
    const maximo = Math.max(6, Math.floor(ancho / 7));
    const corto = texto.length > maximo ? `${texto.slice(0, maximo - 1)}…` : texto;

    return (
        <g transform={`translate(${x},${y})`}>
            <text x={-8} y={0} dy={4} textAnchor="end" fill="var(--muted-foreground)" fontSize={12}>
                <title>{texto}</title>
                {corto}
            </text>
        </g>
    );
}

interface PropsBarrasH {
    datos: { etiqueta: string | number; total: number }[];
    etiqueta: string;
    color?: string;
    nombreSerie?: string;
    anchoEtiquetas?: number;
    vacio?: string;
    unidad?: string;
}

/** Barras horizontales por categoría (una serie, un solo color) con el valor al final de cada barra. */
export function GraficoBarrasH({ datos, etiqueta, color = 'var(--chart-1)', nombreSerie = 'Total', anchoEtiquetas = 150, vacio = 'Sin datos para mostrar.', unidad }: PropsBarrasH) {
    const r = useRadio();
    const filas = datos.map((d) => ({ etiqueta: String(d.etiqueta ?? '—'), total: Number(d.total) || 0 }));

    if (!filas.length) {
        return <p className="text-sm text-muted-foreground">{vacio}</p>;
    }

    const alto = Math.max(96, filas.length * 34 + 12);

    return (
        <figure aria-label={etiqueta} className="m-0 w-full min-w-0" style={{ height: alto }}>
            <ResponsiveContainer width="100%" height="100%">
                <BarChart data={filas} layout="vertical" margin={{ top: 0, right: 44, bottom: 0, left: 0 }} barCategoryGap="24%">
                    <XAxis type="number" hide allowDecimals={false} domain={[0, 'dataMax']} />
                    <YAxis
                        type="category"
                        dataKey="etiqueta"
                        width={anchoEtiquetas}
                        tickLine={false}
                        axisLine={false}
                        interval={0}
                        tick={(p: YAxisTickContentProps) => <EtiquetaCategoria {...p} ancho={anchoEtiquetas} />}
                    />
                    <Tooltip cursor={{ fill: 'var(--muted)', fillOpacity: 0.7 }} content={(p) => <ContenidoTooltip {...p} unidad={unidad} />} />
                    {/* minPointSize: las categorías en cero muestran una marca mínima con su valor "0". */}
                    <Bar dataKey="total" name={nombreSerie} fill={color} maxBarSize={20} radius={[0, r, r, 0]} minPointSize={2}>
                        <LabelList dataKey="total" position="right" fill="var(--foreground)" fontSize={12} formatter={(v) => numero(Number(v ?? 0))} />
                    </Bar>
                </BarChart>
            </ResponsiveContainer>
        </figure>
    );
}
