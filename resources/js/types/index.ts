/*
 * Tipos compartidos por la SPA. Reflejan el contrato JSON de la API RESTful (Capa 2).
 */

export type Tema = 'ninos' | 'jovenes' | 'adultos';
export type Modo = 'auto' | 'dia' | 'noche';

export interface Preferencias {
    tema: Tema;
    modo: Modo;
    /** Escala tipográfica en porcentaje (85 - 150). */
    fuente: number;
    contraste: boolean;
}

export interface Usuario {
    id: number;
    name: string;
    email: string;
    telefono: string | null;
    rol: string | null;
    panel: boolean;
    es_graduado: boolean;
    permisos: string[];
    preferencias: Preferencias | null;
    ultimo_acceso: string | null;
    created_at: string | null;
}

export interface Opcion {
    value: string | number;
    label: string;
    grupo?: string | null;
}

/** Paginador estándar de Laravel (LengthAwarePaginator serializado). */
export interface Paginado<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links?: { url: string | null; label: string; active: boolean }[];
}

/** Datos del encabezado y pie de página del portal (GET /api/sitio). */
export interface DatosSitio {
    carrera: string;
    facultad: string | null;
    universidad: string | null;
    contacto: {
        direccion: string | null;
        telefonos: string | null;
        whatsapp: string | null;
        correo: string | null;
        horario: string | null;
    };
    redes: Partial<Record<'facebook' | 'instagram' | 'youtube' | 'tiktok' | 'linkedin', string>>;
    menu: {
        institucional: { slug: string; titulo: string }[];
        academico: { slug: string; titulo: string }[];
    };
}

/** Menú dinámico del panel (GET /api/menu), construido desde la base de datos según permisos. */
export interface ItemMenu {
    titulo: string;
    icono: string | null;
    url: string | null;
}

export interface GrupoMenu extends ItemMenu {
    items: ItemMenu[];
}

/** Antispam sin sesión que acompaña a los formularios públicos. */
export interface DatosAntispam {
    token: string;
    pregunta?: string | null;
}

/* ---------------------------- CRUD genérico del panel ---------------------------- */

export interface ReglasCliente {
    requerido?: boolean;
    max?: number;
    min?: number;
    entre?: [number, number];
    email?: boolean;
    url?: boolean;
    numerico?: boolean;
    entero?: boolean;
    fecha?: boolean;
    hora?: boolean;
    imagen?: boolean;
    mimes?: string[];
    confirmado?: boolean;
    enLista?: string[];
}

export type TipoCampo =
    | 'text'
    | 'email'
    | 'url'
    | 'number'
    | 'date'
    | 'time'
    | 'password'
    | 'textarea'
    | 'markdown'
    | 'select'
    | 'multiselect'
    | 'checkboxes'
    | 'checkbox'
    | 'image'
    | 'file'
    | 'readonly'
    | 'matriz';

export interface FilaMatriz {
    recurso: string;
    etiqueta: string;
    modulo: string;
    permisos: Record<string, number | null>;
}

export interface Campo {
    nombre: string;
    etiqueta: string;
    tipo: TipoCampo;
    requerido?: boolean;
    ayuda?: string | null;
    ancho?: 'full' | null;
    opciones?: Opcion[];
    accept?: string | null;
    reglas?: ReglasCliente;
    /** Solo para tipo "matriz". */
    acciones?: string[];
    filas?: FilaMatriz[];
}

export interface AccionCrud {
    etiqueta: string;
    url: string;
    icono?: string | null;
    /** "enlace": ruta de la SPA; "descarga": ruta de la API descargada con Axios. */
    tipo?: 'enlace' | 'descarga';
}

export interface ConfigCrud {
    uri: string;
    recurso: string;
    titulo: string;
    singular: string;
    femenino?: boolean;
    descripcion: string | null;
    permisos: { crear: boolean; editar: boolean; eliminar: boolean };
    acciones: AccionCrud[];
    accionesFila: AccionCrud[];
}

export interface Columna {
    clave: string;
    etiqueta: string;
    tipo?: 'text' | 'image' | 'bool' | 'date' | 'datetime' | 'badge' | 'link';
}

export interface Filtro {
    clave: string;
    etiqueta: string;
    tipo?: 'select' | 'date';
    opciones?: Opcion[];
}

export type Fila = Record<string, unknown> & { id: number };

export interface RespuestaCrudIndex {
    config: ConfigCrud;
    columnas: Columna[];
    filtros: Filtro[];
    buscar: boolean;
    consulta: Record<string, string | null>;
    registros: Paginado<Fila>;
}

export interface RespuestaCrudForm {
    config: ConfigCrud;
    campos: Campo[];
    valores: Record<string, unknown>;
    registroId: number | null;
}
