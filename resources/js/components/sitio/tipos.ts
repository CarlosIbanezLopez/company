import type { DatosAntispam, Paginado } from '@/types';

/*
 * Respuestas JSON de la API pública del portal (Capa 2) que consumen las páginas del sitio.
 * Cada respuesta equivale a las props que recibía la página Vue original.
 */

/** Catálogo clave → etiqueta (tipos, estados, situaciones...). */
export type Catalogo = Record<string, string>;

export interface CategoriaNoticia {
    nombre: string;
    slug: string;
}

export interface NoticiaResumen {
    id: number;
    titulo: string;
    slug: string;
    resumen: string | null;
    imagen_url: string | null;
    fecha_publicacion: string | null;
    destacado: boolean;
    categoria: CategoriaNoticia | null;
}

export interface EventoResumen {
    id: number;
    titulo: string;
    slug: string;
    tipo: string;
    fecha: string;
    hora_inicio: string | null;
    hora_fin: string | null;
    lugar: string | null;
    afiche_url: string | null;
    enlace_inscripcion: string | null;
}

/* ------------------------------------------------------------------ Inicio */

export interface Banner {
    id: number;
    titulo: string;
    subtitulo: string | null;
    imagen_url: string | null;
    enlace: string | null;
    texto_boton: string | null;
}

export interface Destacado {
    id: number;
    titulo: string;
    descripcion: string | null;
    valor: string | null;
    enlace: string | null;
}

export interface EnlacePlataforma {
    id: number;
    nombre: string;
    url: string;
    descripcion: string | null;
}

export interface RespuestaInicio {
    banners: Banner[];
    presentacion: { titulo: string | null; html: string; autor: string | null; video: string | null };
    destacados: Destacado[];
    noticias: NoticiaResumen[];
    eventos: EventoResumen[];
    enlaces: EnlacePlataforma[];
    /** Nombres legibles de los tipos de evento (clave => etiqueta). */
    tipos?: Record<string, string>;
}

/* ------------------------------------------------- Información institucional */

export interface RespuestaPagina {
    pagina: { titulo: string; resumen: string | null; html: string; imagen_url: string | null; slug: string; updated_at?: string | null };
    seccion: string;
    hermanas: { slug: string; titulo: string }[];
    prefijo: string;
}

export interface Persona {
    id: number;
    nombre: string;
    tipo: string;
    cargo: string | null;
    especialidad: string | null;
    materias: string | null;
    formacion: string | null;
    correo: string | null;
    foto_url: string | null;
}

export interface RespuestaPlantel {
    autoridades: Persona[];
    docentes: Persona[];
    q: string | null;
}

export interface Materia {
    id: number;
    sigla: string;
    nombre: string;
    semestre: number;
    creditos: number;
    horas_teoricas: number;
    horas_practicas: number;
    tipo: string;
    descripcion: string | null;
    prerequisitos: number[];
    posteriores: number[];
}

export interface RespuestaMalla {
    materias: Materia[];
    semestres: number[];
}

export interface Actividad {
    id: number;
    titulo: string;
    descripcion: string | null;
    fecha_inicio: string;
    fecha_fin: string | null;
    tipo: string;
}

export interface RespuestaCalendario {
    actividades: Actividad[];
    gestiones: (string | number)[];
    gestion: string | number | null;
    tipos: Catalogo;
}

export interface Pregunta {
    id: number;
    pregunta: string;
    respuesta: string;
}

export interface RespuestaPreguntas {
    grupos: Record<string, Pregunta[]>;
}

/* ------------------------------------------- Biblioteca, investigación y revista */

export interface Documento {
    id: number;
    titulo: string;
    descripcion: string | null;
    fecha_publicacion: string | null;
    descargas: number;
    archivo_url: string | null;
    /** Enlace absoluto a la descarga pública (suma el contador de descargas). */
    descargar_url?: string | null;
    categoria: { nombre: string; slug: string } | null;
}

export interface CategoriaDocumento {
    id: number;
    nombre: string;
    slug: string;
    descripcion: string | null;
    documentos_count: number;
}

export interface RespuestaDocumentos {
    documentos: Paginado<Documento>;
    categorias: CategoriaDocumento[];
    filtros: { q?: string; categoria?: string };
}

export interface LineaInvestigacion {
    id: number;
    nombre: string;
    descripcion: string | null;
    proyectos_count: number;
}

export interface ProyectoResumen {
    id: number;
    tipo: string;
    titulo: string;
    slug: string;
    responsables: string | null;
    estado: string;
    fecha_inicio: string | null;
    imagen_url: string | null;
    linea: { nombre: string } | null;
}

export interface RespuestaInvestigacion {
    lineas: LineaInvestigacion[];
    proyectos: ProyectoResumen[];
    estados: Catalogo;
    tipos: Catalogo;
    filtros: { tipo?: string; linea?: string; estado?: string };
}

export interface RespuestaProyecto {
    proyecto: {
        titulo: string;
        tipo: string;
        html: string;
        responsables: string | null;
        estado: string;
        fecha_inicio: string | null;
        fecha_fin: string | null;
        resultados: string | null;
        enlace_publicacion: string | null;
        enlace_repositorio: string | null;
        imagen_url: string | null;
        linea: { nombre: string } | null;
    };
    estados: Catalogo;
    tipos: Catalogo;
}

export interface Edicion {
    id: number;
    numero: number;
    titulo: string;
    fecha_publicacion: string | null;
    descripcion: string | null;
    portada_url: string | null;
    archivo_pdf_url: string | null;
    articulos_count: number;
}

export interface RespuestaRevista {
    ediciones: Edicion[];
    presentacion: string | null;
}

export interface Articulo {
    id: number;
    titulo: string;
    autores: string;
    resumen: string | null;
    palabras_clave: string | null;
    archivo_pdf_url: string | null;
}

export interface RespuestaEdicion {
    edicion: {
        numero: number;
        titulo: string;
        fecha_publicacion: string | null;
        descripcion: string | null;
        portada_url: string | null;
        archivo_pdf_url: string | null;
        articulos: Articulo[];
    };
    otras: { id: number; numero: number; titulo: string; portada_url: string | null }[];
}

/* ------------------------------------------------ Vinculación, INNOVA y graduados */

export interface Convenio {
    id: number;
    institucion: string;
    tipo: string;
    finalidad: string | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    logo_url: string | null;
    estado: string;
}

export interface RespuestaVinculacion {
    convenios: Convenio[];
    tipos: Catalogo;
    estados: Catalogo;
    tipo: string | null;
}

export interface ServicioInnova {
    id: number;
    nombre: string;
    descripcion: string | null;
}

export interface RespuestaInnova {
    descripcion: { titulo: string; html: string } | null;
    servicios: ServicioInnova[];
    integrantes: { id: number; nombre: string; cargo: string | null; semestre: string | null; foto_url: string | null }[];
    proyectos: { id: number; titulo: string; cliente: string | null; descripcion: string | null; fecha: string | null; imagen_url: string | null }[];
    antispam: DatosAntispam;
}

export interface RespuestaGraduados {
    paginas: { slug: string; titulo: string; resumen: string | null; html: string }[];
    situaciones: Catalogo;
    modalidades: Catalogo;
    tieneFicha: boolean;
    antispam: DatosAntispam;
    total: number;
}

export interface FichaGraduado {
    id?: number;
    nombre: string | null;
    ci: string | null;
    registro: string | null;
    anio_graduacion: number | string | null;
    modalidad_titulacion: string | null;
    situacion_laboral: string | null;
    empresa: string | null;
    cargo: string | null;
    ciudad: string | null;
    telefono: string | null;
    linkedin: string | null;
    correo: string | null;
    updated_at?: string | null;
}

export interface RespuestaMiFicha {
    graduado: FichaGraduado | null;
    situaciones: Catalogo;
    modalidades: Catalogo;
}

/** Respuesta de los formularios públicos: mensaje y un token antispam nuevo para otro envío. */
export interface RespuestaEnvio {
    message: string;
    antispam?: DatosAntispam;
}

/* ------------------------------------------------- Noticias, eventos y galería */

export interface RespuestaNoticias {
    noticias: Paginado<NoticiaResumen>;
    categorias: CategoriaNoticia[];
    categoria: string | null;
}

export interface RespuestaNoticia {
    noticia: {
        titulo: string;
        resumen: string | null;
        html: string;
        imagen_url: string | null;
        fecha_publicacion: string | null;
        categoria: CategoriaNoticia | null;
        autor: { name: string } | null;
    };
    relacionadas: { id: number; titulo: string; slug: string; fecha_publicacion: string | null; imagen_url: string | null }[];
}

export interface RespuestaEventos {
    proximos: EventoResumen[];
    historial: Paginado<EventoResumen>;
    tipos: Catalogo;
}

export interface RespuestaEvento {
    evento: EventoResumen & { html: string; finalizado: boolean };
    tipos: Catalogo;
}

export interface AlbumResumen {
    id: number;
    titulo: string;
    slug: string;
    descripcion: string | null;
    fecha: string | null;
    portada_url: string | null;
    fotos_count: number;
}

export interface Video {
    id: number;
    titulo: string;
    url: string;
    descripcion?: string | null;
}

export interface RespuestaGaleria {
    albumes: AlbumResumen[];
    videos: Video[];
}

export interface Foto {
    id: number;
    imagen_url: string | null;
    descripcion: string | null;
}

export interface RespuestaAlbum {
    album: {
        titulo: string;
        descripcion: string | null;
        fecha: string | null;
        fotos: Foto[];
        videos: Video[];
    };
}

/* ----------------------------------------------------- Contacto y búsqueda */

export interface RespuestaContacto {
    categorias: { id: number; nombre: string }[];
    mapa: string | null;
    antispam: DatosAntispam;
}

export interface ResultadoBusqueda {
    titulo: string;
    extracto: string | null;
    url: string;
    fecha: string | null;
}

export interface RespuestaBuscar {
    q: string;
    grupos: { seccion: string; resultados: ResultadoBusqueda[] }[];
    total: number;
}

export interface Sugerencia {
    titulo: string;
    seccion: string;
    url: string;
}

export interface RespuestaSugerencias {
    q: string;
    sugerencias: Sugerencia[];
}
