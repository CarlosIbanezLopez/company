import { ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link } from 'react-router';

export interface Miga {
    titulo: string;
    /** Ruta de la SPA; sin url la miga es solo texto. */
    url?: string;
}

interface PropsEncabezado {
    titulo: string;
    descripcion?: string | null;
    migas?: Miga[];
    /** Contenido adicional bajo el título (datos, buscador...). */
    children?: ReactNode;
}

/** Cabecera de cada página interna: ruta de navegación (migas), título principal (h1) y descripción. */
export function Encabezado({ titulo, descripcion, migas = [], children }: PropsEncabezado) {
    return (
        <section className="border-b bg-primary text-primary-foreground dark:bg-card dark:text-foreground">
            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 md:py-14">
                <nav className="mb-4" aria-label="Ruta de navegación">
                    <ol className="flex flex-wrap items-center gap-1 text-xs tracking-wider uppercase opacity-90">
                        <li>
                            <Link to="/" className="hover:underline">
                                Inicio
                            </Link>
                        </li>
                        {migas.map((miga, i) => (
                            <li key={`${miga.titulo}-${i}`} className="inline-flex items-center gap-1">
                                <ChevronRight className="size-3" aria-hidden="true" />
                                {miga.url ? (
                                    <Link to={miga.url} className="hover:underline">
                                        {miga.titulo}
                                    </Link>
                                ) : (
                                    <span aria-current={i === migas.length - 1 ? 'page' : undefined}>{miga.titulo}</span>
                                )}
                            </li>
                        ))}
                    </ol>
                </nav>
                <div className="flex items-start gap-4">
                    <span className="mt-1 hidden h-10 w-1.5 shrink-0 bg-marca sm:block" aria-hidden="true" />
                    <div className="min-w-0">
                        <h1 className="text-3xl font-bold tracking-tight text-balance break-words md:text-4xl">{titulo}</h1>
                        {descripcion && <p className="mt-3 max-w-3xl text-base opacity-90 md:text-lg">{descripcion}</p>}
                    </div>
                </div>
                {children}
            </div>
        </section>
    );
}
