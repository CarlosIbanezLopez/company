import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/** Une clases de Tailwind resolviendo conflictos: cn('px-2', condicion && 'px-4'). */
export function cn(...entradas: ClassValue[]): string {
    return twMerge(clsx(entradas));
}
