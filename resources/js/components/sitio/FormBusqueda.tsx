import { Search } from 'lucide-react';
import { useId, useState } from 'react';
import type { FormEvent } from 'react';
import { cn } from '@/lib/utils';

interface PropsFormBusqueda {
    /** Texto actual de la búsqueda (normalmente el parámetro ?q= de la URL). */
    valor: string;
    onBuscar: (texto: string) => void;
    /** Nombre accesible del formulario y del campo. */
    etiqueta: string;
    placeholder?: string;
    className?: string;
}

/** Formulario de búsqueda compacto (campo + botón) que se sincroniza con el valor de la URL. */
export function FormBusqueda({ valor, onBuscar, etiqueta, placeholder, className }: PropsFormBusqueda) {
    const id = useId();
    const [texto, setTexto] = useState(valor);
    const [previo, setPrevio] = useState(valor);

    // Si la búsqueda cambia desde fuera (atrás/adelante del navegador) se actualiza el campo.
    if (valor !== previo) {
        setPrevio(valor);
        setTexto(valor);
    }

    const enviar = (e: FormEvent) => {
        e.preventDefault();
        onBuscar(texto.trim());
    };

    return (
        <form role="search" aria-label={etiqueta} className={cn('flex w-full', className)} onSubmit={enviar}>
            <label htmlFor={id} className="sr-only">
                {etiqueta}
            </label>
            <input
                id={id}
                type="search"
                value={texto}
                maxLength={100}
                onChange={(e) => setTexto(e.target.value)}
                placeholder={placeholder}
                className="campo min-w-0 rounded-r-none"
            />
            <button type="submit" className="boton w-11 shrink-0 rounded-l-none px-0" aria-label="Buscar">
                <Search className="size-4" aria-hidden="true" />
            </button>
        </form>
    );
}
