export type EnlaceMenu = { slug: string; titulo: string; seccion?: string };

export type DatosSitio = {
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
    redes: Record<string, string>;
    menu: {
        institucional: EnlaceMenu[];
        academico: EnlaceMenu[];
    };
};

export type GrupoMenuAdmin = {
    titulo: string;
    icono: string | null;
    url: string | null;
    items: { titulo: string; icono: string | null; url: string | null }[];
};

export type Paginado<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type Opcion = { value: string | number; label: string; grupo?: string };
