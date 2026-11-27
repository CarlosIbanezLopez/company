import type { ReactNode } from 'react';
import { etiquetaEstado } from '@/lib/formato';
import { cn } from '@/lib/utils';

/*
 * Insignias de estado del panel. Los colores salen de tokens semánticos (exito, aviso, marca,
 * destructive...) para que se adapten a los tres temas, al modo noche y al alto contraste.
 */
export type Tono = 'exito' | 'aviso' | 'marca' | 'peligro' | 'info' | 'neutro' | 'base';

const TONOS: Record<Tono, string> = {
    exito: 'border-exito/40 bg-exito/15 text-exito',
    aviso: 'border-aviso/60 bg-aviso/20 text-foreground',
    marca: 'border-marca/40 bg-marca/15 text-marca',
    peligro: 'border-destructive/40 bg-destructive/10 text-destructive',
    info: 'border-primary/30 bg-primary/10 text-primary',
    neutro: 'border-border bg-muted text-muted-foreground',
    base: 'border-border bg-secondary text-secondary-foreground',
};

/** Color por valor de estado (como en el panel original). */
const TONO_ESTADO: Record<string, Tono> = {
    publicado: 'exito',
    vigente: 'exito',
    atendida: 'exito',
    aceptada: 'exito',
    respondida: 'exito',
    crear: 'exito',
    publicar: 'exito',
    finalizado: 'neutro',
    archivado: 'neutro',
    cerrada: 'neutro',
    archivar: 'neutro',
    cierre_sesion: 'neutro',
    borrador: 'aviso',
    pendiente: 'aviso',
    en_tramite: 'aviso',
    planificado: 'aviso',
    nueva: 'marca',
    eliminar: 'marca',
    rechazada: 'marca',
    en_revision: 'info',
    en_proceso: 'info',
    en_curso: 'info',
    entrevista: 'info',
    editar: 'info',
    inicio_sesion: 'info',
};

/** Textos legibles (con tildes) de los valores internos más comunes. */
const ETIQUETAS: Record<string, string> = {
    en_revision: 'En revisión',
    en_proceso: 'En proceso',
    en_curso: 'En curso',
    en_tramite: 'En trámite',
    inicio_sesion: 'Inicio de sesión',
    cierre_sesion: 'Cierre de sesión',
    busca_empleo: 'Busca empleo',
    proyecto_grado: 'Proyecto de grado',
    trabajo_dirigido: 'Trabajo dirigido',
    examen_grado: 'Examen de grado',
    excelencia: 'Excelencia',
    interaccion_social: 'Interacción social',
    proyecto_conjunto: 'Proyecto conjunto',
    practica: 'Práctica',
    consultoria: 'Consultoría',
    examenes: 'Exámenes',
    academico: 'Académico',
    titulacion: 'Titulación',
    produccion: 'Producción',
    pia: 'PIA',
};

/** "en_revision" -> "En revisión"; valores desconocidos: guiones bajos por espacios y mayúscula inicial. */
export function textoEstado(valor: string): string {
    if (ETIQUETAS[valor]) {
        return ETIQUETAS[valor];
    }

    const texto = etiquetaEstado(valor);

    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

export function tonoEstado(valor: string): Tono {
    return TONO_ESTADO[valor] ?? 'base';
}

interface PropsInsignia {
    tono?: Tono;
    className?: string;
    children: ReactNode;
    title?: string;
}

export function Insignia({ tono = 'base', className, children, title }: PropsInsignia) {
    return (
        <span title={title} className={cn('insignia rounded-lg whitespace-nowrap', TONOS[tono], className)}>
            {children}
        </span>
    );
}

/** Insignia para valores de estado (publicado, nueva, rechazada...). */
export function InsigniaEstado({ valor, className }: { valor: unknown; className?: string }) {
    if (valor === null || valor === undefined || valor === '') {
        return <span className="text-muted-foreground">—</span>;
    }

    const texto = String(valor);

    return (
        <Insignia tono={tonoEstado(texto)} className={className}>
            {textoEstado(texto)}
        </Insignia>
    );
}

const TONO_METODO: Record<string, Tono> = { GET: 'info', POST: 'exito', PUT: 'aviso', PATCH: 'aviso', DELETE: 'peligro' };

/** Método HTTP (GET, POST, PUT, DELETE) de la bitácora de tráfico. */
export function InsigniaMetodo({ metodo }: { metodo: string | null | undefined }) {
    const texto = (metodo ?? '').toUpperCase() || '—';

    return (
        <Insignia tono={TONO_METODO[texto] ?? 'neutro'} className="font-mono text-[11px]">
            {texto}
        </Insignia>
    );
}

/** Código de respuesta HTTP: 2xx éxito, 3xx neutro, 4xx aviso y 5xx error. */
export function InsigniaHttp({ estado }: { estado: number | string | null | undefined }) {
    const n = Number(estado);
    const tono: Tono = n >= 500 ? 'peligro' : n >= 400 ? 'aviso' : n >= 300 ? 'neutro' : n >= 200 ? 'exito' : 'neutro';

    return (
        <Insignia tono={tono} className="font-mono text-[11px]" title={`Código de respuesta HTTP ${n || '—'}`}>
            {n || '—'}
        </Insignia>
    );
}
