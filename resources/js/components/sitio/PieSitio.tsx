import { Clock, Mail, MapPin, Phone } from 'lucide-react';
import { Link } from 'react-router';
import ContadorVisitas from '@/components/ContadorVisitas';
import { RedesSociales } from '@/components/sitio/RedesSociales';
import { useSitio } from '@/contexts/SitioContext';

const CLASE_ENLACE = 'hover:underline';

/** Pie de página del portal: identidad, contacto, redes, enlaces y contador de visitas de la página actual. */
export function PieSitio() {
    const { sitio } = useSitio();
    const { contacto } = sitio;
    const anio = new Date().getFullYear();

    return (
        <footer className="mt-16 border-t-4 border-marca bg-franja text-franja-foreground">
            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p className="text-xs font-semibold tracking-[0.16em] text-franja-foreground/85 uppercase">Carrera de</p>
                    <p className="mt-1 text-xl font-bold">{sitio.carrera}</p>
                    {sitio.facultad && <p className="mt-3 text-sm">{sitio.facultad}</p>}
                    {sitio.universidad && <p className="text-sm">{sitio.universidad}</p>}
                    <RedesSociales className="mt-5" redes={sitio.redes} />
                </div>

                <div>
                    <h2 className="mb-4 text-sm font-bold tracking-wider uppercase">Contacto</h2>
                    <ul className="space-y-3 text-sm">
                        {contacto.direccion && (
                            <li className="flex gap-2">
                                <MapPin className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>{contacto.direccion}</span>
                            </li>
                        )}
                        {contacto.telefonos && (
                            <li className="flex gap-2">
                                <Phone className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>{contacto.telefonos}</span>
                            </li>
                        )}
                        {contacto.correo && (
                            <li className="flex gap-2">
                                <Mail className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <a href={`mailto:${contacto.correo}`} className={`break-all ${CLASE_ENLACE}`}>
                                    {contacto.correo}
                                </a>
                            </li>
                        )}
                        {contacto.horario && (
                            <li className="flex gap-2 text-franja-foreground/85">
                                <Clock className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>{contacto.horario}</span>
                            </li>
                        )}
                    </ul>
                </div>

                <nav aria-labelledby="pie-carrera">
                    <h2 id="pie-carrera" className="mb-4 text-sm font-bold tracking-wider uppercase">
                        La Carrera
                    </h2>
                    <ul className="space-y-2 text-sm">
                        {sitio.menu.institucional.slice(0, 5).map((p) => (
                            <li key={p.slug}>
                                <Link to={`/institucional/${p.slug}`} className={CLASE_ENLACE}>
                                    {p.titulo}
                                </Link>
                            </li>
                        ))}
                        <li>
                            <Link to="/plantel-docente" className={CLASE_ENLACE}>
                                Autoridades y docentes
                            </Link>
                        </li>
                        <li>
                            <Link to="/malla-curricular" className={CLASE_ENLACE}>
                                Malla curricular
                            </Link>
                        </li>
                    </ul>
                </nav>

                <nav aria-labelledby="pie-accesos">
                    <h2 id="pie-accesos" className="mb-4 text-sm font-bold tracking-wider uppercase">
                        Accesos rápidos
                    </h2>
                    <ul className="space-y-2 text-sm">
                        <li>
                            <Link to="/biblioteca" className={CLASE_ENLACE}>
                                Biblioteca digital
                            </Link>
                        </li>
                        <li>
                            <Link to="/revista-innovar" className={CLASE_ENLACE}>
                                Revista INNOVAR
                            </Link>
                        </li>
                        <li>
                            <Link to="/innova" className={CLASE_ENLACE}>
                                Empresa Junior INNOVA
                            </Link>
                        </li>
                        <li>
                            <Link to="/graduados" className={CLASE_ENLACE}>
                                Programa Alumni
                            </Link>
                        </li>
                        <li>
                            <Link to="/calendario-academico" className={CLASE_ENLACE}>
                                Calendario académico
                            </Link>
                        </li>
                        <li>
                            <Link to="/contacto" className={CLASE_ENLACE}>
                                Formulario de contacto
                            </Link>
                        </li>
                        <li>
                            <Link to="/buscar" className={CLASE_ENLACE}>
                                Buscador del sitio
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>

            <div className="border-t border-franja-foreground/15">
                <div className="mx-auto flex max-w-7xl flex-col items-start justify-between gap-2 px-4 py-4 text-xs text-franja-foreground/85 sm:flex-row sm:items-center sm:px-6">
                    <p>
                        © {anio} Carrera de {sitio.carrera} · FCEE · UAGRM
                    </p>
                    <ContadorVisitas />
                </div>
            </div>
        </footer>
    );
}
