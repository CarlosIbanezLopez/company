import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { ANIO_MAXIMO_GRADUACION } from '@/components/sitio/formularios';
import type { CampoGraduado, ValoresGraduado } from '@/components/sitio/formularios';
import type { Catalogo } from '@/components/sitio/tipos';

interface PropsCampos {
    valores: ValoresGraduado;
    errores: Record<string, string>;
    onCambiar: (campo: CampoGraduado, valor: string) => void;
    /** Validación del campo al salir de él (onBlur). */
    onSalir: (campo: CampoGraduado) => void;
    situaciones: Catalogo;
    modalidades: Catalogo;
}

/** Campos compartidos del registro y de la ficha del graduado (RF-11), dentro de una grilla de 2 columnas. */
export function CamposGraduado({ valores, errores, onCambiar, onSalir, situaciones, modalidades }: PropsCampos) {
    const control = (campo: CampoGraduado) => ({
        name: campo,
        value: valores[campo],
        onChange: (e: { target: { value: string } }) => onCambiar(campo, e.target.value),
        onBlur: () => onSalir(campo),
    });

    return (
        <>
            <CampoFormulario etiqueta="Nombre completo" requerido error={errores.nombre}>
                <input {...control('nombre')} className="campo" maxLength={150} autoComplete="name" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Carnet de identidad" requerido error={errores.ci}>
                <input {...control('ci')} className="campo" maxLength={20} />
            </CampoFormulario>
            <CampoFormulario etiqueta="Registro universitario" error={errores.registro}>
                <input {...control('registro')} className="campo" maxLength={20} />
            </CampoFormulario>
            <CampoFormulario etiqueta="Año de graduación" requerido error={errores.anio_graduacion} ayuda={`Entre 1970 y ${ANIO_MAXIMO_GRADUACION}.`}>
                <input {...control('anio_graduacion')} type="number" inputMode="numeric" min={1970} max={ANIO_MAXIMO_GRADUACION} className="campo" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Modalidad de titulación" error={errores.modalidad_titulacion}>
                <select {...control('modalidad_titulacion')} className="campo">
                    <option value="">Seleccione...</option>
                    {Object.entries(modalidades).map(([clave, nombre]) => (
                        <option key={clave} value={clave}>
                            {nombre}
                        </option>
                    ))}
                </select>
            </CampoFormulario>
            <CampoFormulario etiqueta="Situación laboral actual" requerido error={errores.situacion_laboral}>
                <select {...control('situacion_laboral')} className="campo">
                    <option value="">Seleccione...</option>
                    {Object.entries(situaciones).map(([clave, nombre]) => (
                        <option key={clave} value={clave}>
                            {nombre}
                        </option>
                    ))}
                </select>
            </CampoFormulario>
            <CampoFormulario etiqueta="Empresa / institución donde trabaja" error={errores.empresa}>
                <input {...control('empresa')} className="campo" maxLength={150} autoComplete="organization" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Cargo" error={errores.cargo}>
                <input {...control('cargo')} className="campo" maxLength={120} autoComplete="organization-title" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Ciudad" error={errores.ciudad}>
                <input {...control('ciudad')} className="campo" maxLength={80} autoComplete="address-level2" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Teléfono" error={errores.telefono}>
                <input {...control('telefono')} type="tel" className="campo" maxLength={30} autoComplete="tel" />
            </CampoFormulario>
            <CampoFormulario etiqueta="Perfil de LinkedIn (URL)" error={errores.linkedin} className="md:col-span-2">
                <input {...control('linkedin')} type="url" className="campo" maxLength={255} placeholder="https://www.linkedin.com/in/..." />
            </CampoFormulario>
        </>
    );
}
