import { LoaderCircle, Trash2, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useParams } from 'react-router';
import { toast } from 'sonner';
import { EncabezadoPagina } from '@/components/admin/EncabezadoPagina';
import { conProgreso, tamanoLegible } from '@/components/admin/subidas';
import { ConPermiso } from '@/components/Guardias';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { ErrorCarga, Esqueleto, Vacio } from '@/components/ui/Cargando';
import { Confirmar } from '@/components/ui/Modal';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { api, construirFormData, erroresValidacion, mensajeError } from '@/lib/api';
import { plural } from '@/lib/formato';
import { cn } from '@/lib/utils';
import { reglasDesdeServidor, validarCampo } from '@/lib/validacion';
import type { ReglasCliente } from '@/types';

interface FotoAlbum {
    id: number;
    imagen?: string | null;
    imagen_url: string | null;
    descripcion: string | null;
}

/** GET /api/admin/albumes/{id}/fotos (mismas props que la página Fotos original, más permisos y reglas). */
interface RespuestaFotos {
    album: { id: number; titulo: string; fotos: FotoAlbum[] };
    urls?: { subir: string; eliminar: string; volver: string };
    permisos?: { subir: boolean; eliminar: boolean };
    reglas?: Record<string, ReglasCliente>;
}

/** Reglas por defecto (las mismas del servidor) si la API todavía no las envía. */
const REGLAS_FOTOS: ReglasCliente = { requerido: true, max: 20 };
const REGLAS_FOTO: ReglasCliente = { imagen: true, mimes: ['jpg', 'jpeg', 'png', 'webp'], max: 5120 };
const REGLAS_DESCRIPCION: ReglasCliente = { max: 255 };

function GaleriaFotos({ id }: { id: string }) {
    const { datos, error, estado, recargar, setDatos } = useApi<RespuestaFotos>(`admin/albumes/${id}/fotos`);
    const [archivos, setArchivos] = useState<File[]>([]);
    const [descripcion, setDescripcion] = useState('');
    const [errores, setErrores] = useState<Record<string, string>>({});
    const [subiendo, setSubiendo] = useState(false);
    const [progreso, setProgreso] = useState<number | null>(null);
    const [porEliminar, setPorEliminar] = useState<FotoAlbum | null>(null);
    const [eliminando, setEliminando] = useState(false);
    const entrada = useRef<HTMLInputElement>(null);

    const vistas = useMemo(() => archivos.map((a) => URL.createObjectURL(a)), [archivos]);

    useEffect(() => () => vistas.forEach((url) => URL.revokeObjectURL(url)), [vistas]);

    useTitulo(datos ? `Fotos - ${datos.album.titulo}` : 'Fotografías del álbum');

    if (!datos) {
        return error ? (
            <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />
        ) : (
            <div className="space-y-5" aria-busy="true">
                <Esqueleto className="h-16 w-full" />
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    {Array.from({ length: 10 }, (_, i) => (
                        <Esqueleto key={i} className="aspect-square" />
                    ))}
                </div>
            </div>
        );
    }

    const { album } = datos;
    const fotos = album.fotos ?? [];
    const puedeSubir = datos.permisos?.subir ?? true;
    const puedeEliminar = datos.permisos?.eliminar ?? true;
    const urlSubir = datos.urls?.subir ?? `admin/albumes/${album.id}/fotos`;
    const urlEliminar = datos.urls?.eliminar ?? `admin/albumes/${album.id}/fotos/__FOTO__`;

    /** Validación en el cliente con las mismas reglas que el servidor (fotos, fotos.* y descripcion). */
    const validarSeleccion = (lista: File[], texto: string): Record<string, string> => {
        const encontrados: Record<string, string> = {};
        const mensajeLista = validarCampo(lista, reglasDesdeServidor(datos.reglas?.fotos ?? REGLAS_FOTOS, 'checkboxes'), 'fotografías');

        if (mensajeLista) {
            encontrados.fotos = mensajeLista;
        } else {
            const reglasFoto = reglasDesdeServidor(datos.reglas?.['fotos.*'] ?? REGLAS_FOTO, 'image');

            lista.forEach((archivo, i) => {
                const mensaje = validarCampo(archivo, reglasFoto, `«${archivo.name}»`);

                if (mensaje) {
                    encontrados[`foto_${i}`] = mensaje;
                    encontrados.fotos ??= 'Hay archivos que no cumplen los requisitos: quítelos o cámbielos.';
                }
            });
        }

        const mensajeDescripcion = validarCampo(texto, reglasDesdeServidor(datos.reglas?.descripcion ?? REGLAS_DESCRIPCION, 'text'), 'descripción');

        if (mensajeDescripcion) {
            encontrados.descripcion = mensajeDescripcion;
        }

        return encontrados;
    };

    const seleccionar = (lista: FileList | null) => {
        const nuevos = [...archivos, ...Array.from(lista ?? [])];
        setArchivos(nuevos);
        setErrores(nuevos.length ? validarSeleccion(nuevos, descripcion) : {});

        if (entrada.current) {
            entrada.current.value = '';
        }
    };

    const quitar = (indice: number) => {
        const nuevos = archivos.filter((_, i) => i !== indice);
        setArchivos(nuevos);
        setErrores(nuevos.length ? validarSeleccion(nuevos, descripcion) : {});
    };

    const subir = async (evento: React.FormEvent) => {
        evento.preventDefault();
        const encontrados = validarSeleccion(archivos, descripcion);
        setErrores(encontrados);

        if (Object.keys(encontrados).length) {
            toast.error('Revise las fotografías seleccionadas.');
            entrada.current?.focus();

            return;
        }

        setSubiendo(true);

        try {
            const { data } = await api.post<RespuestaFotos & { message?: string }>(urlSubir, construirFormData({ fotos: archivos, descripcion }), conProgreso(setProgreso));
            toast.success(data?.message ?? 'Fotografías agregadas al álbum.');
            setArchivos([]);
            setDescripcion('');

            if (data?.album) {
                setDatos((actual) => (actual ? { ...actual, ...data } : data));
            } else {
                recargar();
            }
        } catch (e) {
            const delServidor = erroresValidacion(e);
            setErrores(delServidor);
            toast.error(mensajeError(e));
        } finally {
            setSubiendo(false);
            setProgreso(null);
        }
    };

    const eliminar = async () => {
        if (!porEliminar) {
            return;
        }

        setEliminando(true);

        try {
            const { data } = await api.delete<{ message?: string }>(urlEliminar.replace('__FOTO__', String(porEliminar.id)));
            toast.success(data?.message ?? 'Fotografía eliminada.');
            const idFoto = porEliminar.id;
            setDatos((actual) => (actual ? { ...actual, album: { ...actual.album, fotos: actual.album.fotos.filter((f) => f.id !== idFoto) } } : actual));
        } catch (e) {
            toast.error(mensajeError(e));
        } finally {
            setEliminando(false);
            setPorEliminar(null);
        }
    };

    return (
        <div className="space-y-5">
            <EncabezadoPagina
                volver={{ to: datos.urls?.volver ?? '/admin/albumes', texto: 'Álbumes de fotos' }}
                titulo={`Fotografías: ${album.titulo}`}
                descripcion={`${plural(fotos.length, 'fotografía')} en el álbum. Suba hasta 20 imágenes a la vez (JPG, PNG o WEBP, máximo 5 MB cada una).`}
            />

            {puedeSubir && (
                <form noValidate onSubmit={(e) => void subir(e)} className="tarjeta overflow-hidden" aria-busy={subiendo}>
                    <div className="grid gap-4 p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-start md:p-5">
                        <CampoFormulario etiqueta="Imágenes" error={errores.fotos} ayuda="Puede elegir varias a la vez; se agregan a la selección." requerido>
                            <input
                                ref={entrada}
                                name="fotos"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/webp"
                                onChange={(e) => seleccionar(e.target.files)}
                                className="campo pt-1.5 file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1 file:text-sm file:font-medium file:text-secondary-foreground"
                            />
                        </CampoFormulario>
                        <CampoFormulario etiqueta="Descripción (opcional)" error={errores.descripcion} ayuda="Se aplica a todas las fotos de esta subida.">
                            <input
                                name="descripcion"
                                value={descripcion}
                                onChange={(e) => {
                                    setDescripcion(e.target.value);
                                    setErrores((actual) => {
                                        const resto = { ...actual };
                                        delete resto.descripcion;

                                        return resto;
                                    });
                                }}
                                className="campo"
                            />
                        </CampoFormulario>
                        <button type="submit" className="boton md:mt-7" disabled={subiendo || !archivos.length}>
                            {subiendo ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : <Upload className="size-4" aria-hidden="true" />}
                            {subiendo ? 'Subiendo…' : archivos.length ? `Subir ${plural(archivos.length, 'foto')}` : 'Subir fotos'}
                        </button>
                    </div>

                    {archivos.length > 0 && (
                        <div className="border-t bg-muted/40 p-4 md:p-5">
                            <p className="mb-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase">Selección ({archivos.length})</p>
                            <ul className="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                                {archivos.map((archivo, i) => (
                                    <li key={`${archivo.name}-${i}`} className={cn('relative overflow-hidden rounded-lg border bg-card', errores[`foto_${i}`] && 'border-destructive')}>
                                        <img src={vistas[i]} alt={`Vista previa de ${archivo.name}`} className="aspect-square w-full object-cover" />
                                        <div className="px-2 py-1.5 text-xs">
                                            <p className="truncate font-medium" title={archivo.name}>
                                                {archivo.name}
                                            </p>
                                            <p className="text-muted-foreground">{tamanoLegible(archivo.size)}</p>
                                            {errores[`foto_${i}`] && (
                                                <p role="alert" className="mt-1 font-medium text-destructive">
                                                    {errores[`foto_${i}`]}
                                                </p>
                                            )}
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => quitar(i)}
                                            disabled={subiendo}
                                            className="absolute top-1.5 right-1.5 inline-flex size-7 items-center justify-center rounded-md bg-card/90 text-foreground shadow hover:bg-destructive hover:text-destructive-foreground"
                                            aria-label={`Quitar ${archivo.name} de la selección`}
                                        >
                                            <X className="size-4" aria-hidden="true" />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {progreso !== null && (
                        <div className="h-1 bg-muted" role="progressbar" aria-label="Subiendo fotografías" aria-valuemin={0} aria-valuemax={100} aria-valuenow={progreso}>
                            <div className="h-1 bg-primary transition-[width]" style={{ width: `${progreso}%` }} />
                        </div>
                    )}
                </form>
            )}

            {fotos.length ? (
                <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    {fotos.map((f) => (
                        <li key={f.id}>
                            <figure className="group relative overflow-hidden rounded-lg border bg-card">
                                <a href={f.imagen_url ?? undefined} target="_blank" rel="noopener noreferrer" className="block">
                                    <img src={f.imagen_url ?? undefined} alt={f.descripcion ?? `Fotografía ${f.id} del álbum ${album.titulo}`} loading="lazy" className="aspect-square w-full object-cover" />
                                    <span className="sr-only"> (abrir en tamaño completo)</span>
                                </a>
                                <figcaption className="truncate px-2 py-1.5 text-xs text-muted-foreground">{f.descripcion ?? 'Sin descripción'}</figcaption>
                                {puedeEliminar && (
                                    <button
                                        type="button"
                                        className="absolute top-2 right-2 inline-flex size-8 items-center justify-center rounded-md bg-destructive text-destructive-foreground shadow transition-opacity focus:opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
                                        aria-label={`Eliminar fotografía ${f.descripcion ?? f.id}`}
                                        onClick={() => setPorEliminar(f)}
                                    >
                                        <Trash2 className="size-4" aria-hidden="true" />
                                    </button>
                                )}
                            </figure>
                        </li>
                    ))}
                </ul>
            ) : (
                <Vacio
                    className="flex flex-col items-center gap-2 py-14"
                    texto={puedeSubir ? 'El álbum todavía no tiene fotografías. Súbalas con el formulario de arriba.' : 'El álbum todavía no tiene fotografías.'}
                />
            )}

            <Confirmar
                abierto={porEliminar !== null}
                titulo="¿Eliminar esta fotografía?"
                mensaje={
                    <span className="flex items-center gap-3">
                        {porEliminar?.imagen_url && <img src={porEliminar.imagen_url} alt="" className="size-16 shrink-0 rounded-lg border object-cover" />}
                        <span>La fotografía se quitará del álbum y del sitio público. Esta acción no se puede deshacer.</span>
                    </span>
                }
                textoConfirmar="Eliminar"
                procesando={eliminando}
                onConfirmar={() => void eliminar()}
                onCancelar={() => !eliminando && setPorEliminar(null)}
            />
        </div>
    );
}

/** CU6 - Fotografías de un álbum de la galería (subir varias a la vez y eliminar). */
export default function Fotos() {
    const { id = '' } = useParams();

    return (
        <ConPermiso permiso="galeria.ver">
            <GaleriaFotos key={id} id={id} />
        </ConPermiso>
    );
}
