import { Eye, PencilLine } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { TextareaHTMLAttributes } from 'react';
import { markdownAHtml } from '@/components/admin/markdown';
import { cn } from '@/lib/utils';

type PropsEditor = Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'value'> & {
    value: string;
    /** Límite de caracteres (muestra un contador). */
    maximo?: number;
    /** Empieza mostrando la vista previa (p. ej. en formularios de solo lectura). */
    vistaInicial?: boolean;
};

/**
 * Editor de Markdown: área de texto con pestañas "Escribir" / "Vista previa".
 * Reenvía id, name y aria-* al <textarea> (compatible con CampoFormulario).
 */
export function EditorMarkdown({ value, maximo, vistaInicial = false, className, disabled, ...props }: PropsEditor) {
    const [vista, setVista] = useState(vistaInicial);
    const html = useMemo(() => (vista ? markdownAHtml(value) : ''), [vista, value]);
    const invalido = Boolean(props['aria-invalid']);

    // Con un error de validación se vuelve a "Escribir" para poder corregir el texto.
    useEffect(() => {
        if (invalido) {
            setVista(false);
        }
    }, [invalido]);
    const boton = (activo: boolean) =>
        cn('inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors', activo ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground');

    return (
        <div
            className={cn(
                'overflow-hidden rounded-lg border border-input bg-background transition-colors focus-within:border-primary focus-within:ring-2 focus-within:ring-ring/25',
                invalido && 'border-destructive',
                className,
            )}
        >
            <div className="flex flex-wrap items-center justify-between gap-2 border-b bg-muted/60 px-2 py-1.5">
                <div className="flex gap-1" role="group" aria-label="Modo del editor">
                    <button type="button" className={boton(!vista)} aria-pressed={!vista} onClick={() => setVista(false)}>
                        <PencilLine className="size-3.5" aria-hidden="true" /> Escribir
                    </button>
                    <button type="button" className={boton(vista)} aria-pressed={vista} onClick={() => setVista(true)}>
                        <Eye className="size-3.5" aria-hidden="true" /> Vista previa
                    </button>
                </div>
                {maximo ? (
                    <span className={cn('text-xs tabular-nums', value.length > maximo ? 'font-semibold text-destructive' : 'text-muted-foreground')}>
                        {value.length.toLocaleString('es-BO')} / {maximo.toLocaleString('es-BO')}
                    </span>
                ) : (
                    <span className="text-xs text-muted-foreground">Markdown</span>
                )}
            </div>
            <textarea
                {...props}
                value={value}
                disabled={disabled}
                hidden={vista}
                className="block min-h-64 w-full resize-y bg-transparent px-3 py-2 font-mono text-[13px] leading-relaxed text-foreground outline-none disabled:opacity-60"
            />
            {vista && (
                <div
                    className="contenido min-h-64 px-4 py-3 text-sm"
                    // El HTML proviene de markdownAHtml, que escapa todo el texto del usuario.
                    dangerouslySetInnerHTML={{ __html: html || '<p>Nada para previsualizar todavía.</p>' }}
                />
            )}
        </div>
    );
}
