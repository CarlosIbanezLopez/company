import { cn } from '@/lib/utils';

interface PropsLogo {
    className?: string;
    /** "claro": para fondos de franja (oscuros); "normal": para fondos claros (barra lateral). */
    variante?: 'normal' | 'claro';
}

/** Monograma "AE" de la Carrera con la franja de marca inferior (identidad del portal). */
export function LogoCarrera({ className, variante = 'normal' }: PropsLogo) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'relative flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-lg text-xs font-black tracking-tight',
                variante === 'claro' ? 'bg-franja-foreground text-franja' : 'bg-primary text-primary-foreground',
                className,
            )}
        >
            AE
            <span className="absolute inset-x-0 bottom-0 h-1 bg-marca" />
        </span>
    );
}
