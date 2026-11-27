import { createContext, useContext, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { api } from '@/lib/api';
import type { DatosSitio } from '@/types';

/** Datos del portal usados por el encabezado y el pie (GET /api/sitio). Se piden una vez. */
const PREDETERMINADOS: DatosSitio = {
    carrera: 'Administración de Empresas',
    facultad: 'Facultad de Ciencias Económicas y Empresariales',
    universidad: 'Universidad Autónoma Gabriel René Moreno',
    contacto: { direccion: null, telefonos: null, whatsapp: null, correo: null, horario: null },
    redes: {},
    menu: { institucional: [], academico: [] },
};

interface ValorSitio {
    sitio: DatosSitio;
    cargado: boolean;
}

const SitioContext = createContext<ValorSitio>({ sitio: PREDETERMINADOS, cargado: false });

export function SitioProvider({ children }: { children: ReactNode }) {
    const [valor, setValor] = useState<ValorSitio>({ sitio: PREDETERMINADOS, cargado: false });

    useEffect(() => {
        api.get<DatosSitio>('sitio')
            .then(({ data }) => setValor({ sitio: { ...PREDETERMINADOS, ...data }, cargado: true }))
            .catch(() => setValor((v) => ({ ...v, cargado: true })));
    }, []);

    return <SitioContext.Provider value={valor}>{children}</SitioContext.Provider>;
}

export function useSitio(): ValorSitio {
    return useContext(SitioContext);
}
