import { Search } from 'lucide-react';
import { useId, useState } from 'react';
import type { FormEvent } from 'react';
import { useSearchParams } from 'react-router';
import { EnlaceFlexible } from '@/components/sitio/Enlace';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos, SinResultados } from '@/components/sitio/Estados';
import { Resaltado } from '@/components/sitio/Resaltado';
import type { RespuestaBuscar } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { plural } from '@/lib/formato';

const MINIMO = 3;

/** Buscador interno: resultados por palabra clave agrupados por sección (RF-17). */
export default function Buscar() {
    const [params, setParams] = useSearchParams();
    const q = (params.get('q') ?? '').trim();
    const valida = q.length >= MINIMO;
    const consulta = useApi<RespuestaBuscar>(valida ? 'buscar' : null, { q });

    useTitulo(q ? `Resultados para «${q}»` : 'Buscar');

    return (
        <>
            <Encabezado
                titulo="Buscador del sitio"
                descripcion="Busque en noticias, eventos, documentos, materias, proyectos de investigación y artículos."
                migas={[{ titulo: 'Buscar' }]}
            >
                <FormularioBusqueda valor={q} onBuscar={(texto) => setParams(texto ? { q: texto } : {})} />
            </Encabezado>

            <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                {!valida ? (
                    <p className="text-muted-foreground">Ingrese una palabra clave de al menos {MINIMO} caracteres.</p>
                ) : (
                    <BloqueDatos consulta={consulta} textoCarga="Buscando…">
                        {({ grupos, total }) => (
                            <>
                                <p className="mb-8 text-sm text-muted-foreground" role="status">
                                    {plural(total, 'resultado', 'resultados')} para <strong className="text-foreground">«{q}»</strong>
                                </p>

                                {grupos.map((g, i) => (
                                    <section key={g.seccion} className="mb-10" aria-labelledby={`grupo-busqueda-${i}`}>
                                        <h2 id={`grupo-busqueda-${i}`} className="mb-3 flex items-center justify-between gap-3 border-b-2 border-primary pb-2 text-lg font-bold">
                                            {g.seccion}
                                            <span className="rounded-lg bg-primary px-2 text-sm text-primary-foreground">
                                                {g.resultados.length}
                                                <span className="sr-only"> {g.resultados.length === 1 ? 'resultado' : 'resultados'}</span>
                                            </span>
                                        </h2>
                                        <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                                            {g.resultados.map((r) => (
                                                <li key={`${r.url}-${r.titulo}`}>
                                                    <EnlaceFlexible href={r.url} className="block p-4 hover:bg-accent focus-visible:bg-accent">
                                                        <span className="block font-semibold text-primary">
                                                            <Resaltado texto={r.titulo} consulta={q} minimo={MINIMO} />
                                                        </span>
                                                        {r.extracto && (
                                                            <span className="mt-1 block text-sm text-muted-foreground">
                                                                <Resaltado texto={r.extracto} consulta={q} minimo={MINIMO} />
                                                            </span>
                                                        )}
                                                        {r.fecha && <span className="mt-1 block text-xs text-muted-foreground">{r.fecha}</span>}
                                                    </EnlaceFlexible>
                                                </li>
                                            ))}
                                        </ul>
                                    </section>
                                ))}

                                {grupos.length === 0 && <SinResultados>No se encontraron resultados. Intente con otras palabras.</SinResultados>}
                            </>
                        )}
                    </BloqueDatos>
                )}
            </div>
        </>
    );
}

function FormularioBusqueda({ valor, onBuscar }: { valor: string; onBuscar: (texto: string) => void }) {
    const id = useId();
    const [texto, setTexto] = useState(valor);
    const [previo, setPrevio] = useState(valor);

    if (valor !== previo) {
        setPrevio(valor);
        setTexto(valor);
    }

    const enviar = (e: FormEvent) => {
        e.preventDefault();
        onBuscar(texto.trim());
    };

    return (
        <form className="mt-6 flex max-w-2xl" role="search" aria-label="Nueva búsqueda" onSubmit={enviar}>
            <label htmlFor={id} className="sr-only">
                Palabras clave
            </label>
            <input
                id={id}
                type="search"
                value={texto}
                maxLength={100}
                onChange={(e) => setTexto(e.target.value)}
                placeholder={`Escriba al menos ${MINIMO} caracteres`}
                className="h-12 w-full min-w-0 rounded-l-lg border-0 bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-marca"
            />
            <button type="submit" className="inline-flex h-12 shrink-0 items-center gap-2 rounded-r-lg bg-marca px-6 font-semibold text-marca-foreground hover:opacity-90">
                <Search className="size-4" aria-hidden="true" /> Buscar
            </button>
        </form>
    );
}
