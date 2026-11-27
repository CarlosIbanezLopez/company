import { cn } from '@/lib/utils';

const ETIQUETAS: Record<string, [corto: string, nombre: string]> = {
    facebook: ['f', 'Facebook'],
    instagram: ['IG', 'Instagram'],
    youtube: ['YT', 'YouTube'],
    tiktok: ['TT', 'TikTok'],
    linkedin: ['in', 'LinkedIn'],
};

/** Enlaces a las redes sociales configuradas en el panel (se abren en una pestaña nueva). */
export function RedesSociales({ redes, className }: { redes: Partial<Record<string, string | null>>; className?: string }) {
    const lista = Object.entries(redes).filter((entrada): entrada is [string, string] => Boolean(entrada[1]));

    if (lista.length === 0) {
        return null;
    }

    return (
        <ul className={cn('flex flex-wrap gap-2', className)} aria-label="Redes sociales">
            {lista.map(([red, url]) => {
                const [corto, nombre] = ETIQUETAS[red] ?? [red.slice(0, 2).toUpperCase(), red];

                return (
                    <li key={red}>
                        <a
                            href={url}
                            target="_blank"
                            rel="noopener noreferrer"
                            title={nombre}
                            aria-label={`${nombre} (se abre en una pestaña nueva)`}
                            className="inline-flex size-9 items-center justify-center rounded-lg border border-current/30 text-xs font-bold transition-colors hover:border-marca hover:bg-marca hover:text-marca-foreground"
                        >
                            <span aria-hidden="true">{corto}</span>
                        </a>
                    </li>
                );
            })}
        </ul>
    );
}
