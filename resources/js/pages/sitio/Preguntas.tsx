import { Plus } from 'lucide-react';
import { Link } from 'react-router';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import type { RespuestaPreguntas } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';

/** Preguntas frecuentes agrupadas por categoría (RF-05). */
export default function Preguntas() {
    useTitulo('Preguntas frecuentes');
    const consulta = useApi<RespuestaPreguntas>('preguntas-frecuentes');

    return (
        <>
            <Encabezado
                titulo="Preguntas frecuentes"
                descripcion="Respuestas a las consultas más comunes de los estudiantes."
                migas={[{ titulo: 'Académica' }, { titulo: 'Preguntas frecuentes' }]}
            />

            <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
                <BloqueDatos consulta={consulta}>
                    {({ grupos }) => {
                        const categorias = Object.entries(grupos ?? {});

                        return categorias.length > 0 ? (
                            categorias.map(([categoria, preguntas], i) => (
                                <section key={categoria} className="mb-10" aria-labelledby={`categoria-${i}`}>
                                    <h2 id={`categoria-${i}`} className="mb-4 border-l-4 border-marca pl-3 text-xl font-bold">
                                        {categoria}
                                    </h2>
                                    <div className="divide-y overflow-hidden rounded-lg border bg-card">
                                        {preguntas.map((p) => (
                                            <details key={p.id} className="group">
                                                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-semibold hover:bg-accent [&::-webkit-details-marker]:hidden">
                                                    {p.pregunta}
                                                    <Plus className="size-4 shrink-0 transition-transform group-open:rotate-45" aria-hidden="true" />
                                                </summary>
                                                <p className="border-t bg-muted/40 px-5 py-4 text-sm leading-relaxed whitespace-pre-line">{p.respuesta}</p>
                                            </details>
                                        ))}
                                    </div>
                                </section>
                            ))
                        ) : (
                            <p className="mb-10 text-muted-foreground">Aún no hay preguntas frecuentes publicadas.</p>
                        );
                    }}
                </BloqueDatos>

                <div className="rounded-lg border bg-card p-6 text-center">
                    <p className="font-semibold">¿No encontró lo que buscaba?</p>
                    <Link to="/contacto" className="boton mt-3">
                        Envíenos su consulta
                    </Link>
                </div>
            </div>
        </>
    );
}
