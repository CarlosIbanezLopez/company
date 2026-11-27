import { Info, LogOut, Save } from 'lucide-react';
import { useState } from 'react';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { CamposGraduado } from '@/components/sitio/CamposGraduado';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import { ETIQUETAS_GRADUADO, reglasGraduado, valoresGraduado } from '@/components/sitio/formularios';
import { useCerrarSesion } from '@/components/sitio/MenuUsuario';
import type { FichaGraduado, RespuestaMiFicha } from '@/components/sitio/tipos';
import { useAuth } from '@/contexts/AuthContext';
import { useApi } from '@/hooks/useApi';
import { useFormulario } from '@/hooks/useFormulario';
import { useTitulo } from '@/hooks/useTitulo';
import { api } from '@/lib/api';
import { fecha } from '@/lib/formato';

/** Ficha personal del graduado con sesión iniciada: consulta y actualización de sus datos (RF-11). */
export default function MiFicha() {
    useTitulo('Mi ficha de graduado');
    const consulta = useApi<RespuestaMiFicha>('graduados/mi-ficha');

    return (
        <>
            <Encabezado
                titulo="Mi ficha de graduado"
                descripcion="Mantenga actualizada su información para el seguimiento a graduados de la Carrera."
                migas={[{ titulo: 'Alumni', url: '/graduados' }, { titulo: 'Mi ficha' }]}
            />

            <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6">
                <BloqueDatos consulta={consulta}>{(datos) => <FormularioFicha datos={datos} />}</BloqueDatos>
            </div>
        </>
    );
}

function FormularioFicha({ datos }: { datos: RespuestaMiFicha }) {
    const { usuario, recargarUsuario } = useAuth();
    const salir = useCerrarSesion();
    const [actualizado, setActualizado] = useState<string | null>(datos.graduado?.updated_at ?? null);
    const [tieneFicha, setTieneFicha] = useState(datos.graduado !== null);

    // Sin ficha todavía: se proponen el nombre, teléfono y correo de la cuenta.
    const valoresDe = (g: FichaGraduado | null) => ({
        ...valoresGraduado(g ?? { nombre: usuario?.name ?? null, telefono: usuario?.telefono ?? null }),
        correo: g?.correo ?? usuario?.email ?? '',
    });

    const f = useFormulario(valoresDe(datos.graduado), {
        // Reglas de GraduadoController@update.
        reglas: { ...reglasGraduado(datos.situaciones, datos.modalidades), correo: ['requerido', 'email', 'max:150'] },
        etiquetas: ETIQUETAS_GRADUADO,
    });

    const guardar = f.alEnviar((valores) => api.put<{ message: string; graduado: FichaGraduado }>('graduados/mi-ficha', valores), {
        onExito: (respuesta) => {
            const graduado = respuesta.data.graduado ?? null;
            setActualizado(graduado?.updated_at ?? new Date().toISOString());
            f.reiniciar(valoresDe(graduado));

            // Al crear la ficha la cuenta pasa a ser de graduado (actualiza el menú de la cuenta).
            if (!tieneFicha) {
                setTieneFicha(true);
                void recargarUsuario();
            }
        },
    });

    return (
        <>
            {actualizado ? (
                <p className="mb-4 text-sm text-muted-foreground">Última actualización: {fecha(actualizado)}</p>
            ) : (
                <p className="mb-4 flex items-start gap-2 rounded-lg border-l-4 border-primary bg-card p-4 text-sm">
                    <Info className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true" />
                    Aún no registró su ficha de graduado. Complete sus datos y guárdelos.
                </p>
            )}

            <form className="tarjeta grid gap-5 p-6 md:grid-cols-2" noValidate onSubmit={guardar} aria-label="Ficha de graduado">
                <p className="text-sm text-muted-foreground md:col-span-2">Los campos marcados con * son obligatorios.</p>
                <CamposGraduado
                    valores={f.valores}
                    errores={f.errores}
                    onCambiar={(campo, valor) => f.set(campo, valor)}
                    onSalir={(campo) => f.validarUno(campo)}
                    situaciones={datos.situaciones}
                    modalidades={datos.modalidades}
                />
                <CampoFormulario etiqueta="Correo de contacto" requerido error={f.errores.correo} className="md:col-span-2">
                    <input
                        name="correo"
                        type="email"
                        className="campo"
                        autoComplete="email"
                        maxLength={150}
                        value={f.valores.correo}
                        onChange={(e) => f.set('correo', e.target.value)}
                        onBlur={() => f.validarUno('correo')}
                    />
                </CampoFormulario>
                <div className="flex flex-wrap items-center justify-between gap-4 md:col-span-2">
                    <button type="submit" className="boton" disabled={f.enviando}>
                        <Save className="size-4" aria-hidden="true" /> {f.enviando ? 'Guardando…' : 'Guardar cambios'}
                    </button>
                    <button type="button" className="boton-borde" onClick={() => void salir()}>
                        <LogOut className="size-4" aria-hidden="true" /> Cerrar sesión
                    </button>
                </div>
            </form>
        </>
    );
}
