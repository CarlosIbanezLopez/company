import { ImageOff } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

interface PropsImagen {
    src?: string | null;
    alt: string;
    className?: string;
}

/**
 * Imagen que ocupa su contenedor (object-cover) con carga diferida.
 * Si no hay imagen o no se puede descargar, muestra un marcador accesible.
 */
export function Imagen({ src, alt, className }: PropsImagen) {
    const [fallo, setFallo] = useState(false);
    const [srcPrevio, setSrcPrevio] = useState(src);

    // Si cambia la imagen se vuelve a intentar mostrarla.
    if (src !== srcPrevio) {
        setSrcPrevio(src);
        setFallo(false);
    }

    if (src && !fallo) {
        return <img src={src} alt={alt} loading="lazy" decoding="async" className={cn('h-full w-full object-cover', className)} onError={() => setFallo(true)} />;
    }

    return (
        <div
            className={cn('flex h-full w-full items-center justify-center bg-muted text-muted-foreground', className)}
            role={alt ? 'img' : undefined}
            aria-label={alt || undefined}
            aria-hidden={alt ? undefined : true}
        >
            <ImageOff className="size-8 opacity-50" aria-hidden="true" />
        </div>
    );
}
