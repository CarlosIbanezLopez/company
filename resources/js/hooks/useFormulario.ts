import { useCallback, useRef, useState } from 'react';
import { toast } from 'sonner';
import { erroresValidacion, mensajeError } from '@/lib/api';
import { validar, validarCampo } from '@/lib/validacion';
import type { Etiquetas, ReglasFormulario } from '@/lib/validacion';

export interface OpcionesFormulario {
    /** Reglas del validador del cliente (lib/validacion). */
    reglas?: ReglasFormulario;
    /** Nombres legibles de los campos para los mensajes ("correo electrónico"). */
    etiquetas?: Etiquetas;
}

export interface OpcionesEnvio<R> {
    /** Mensaje de éxito; si no se indica se usa `message` de la respuesta de la API. */
    mensajeExito?: string | false;
    onExito?: (resultado: R) => void;
}

/**
 * Estado de un formulario con validación en el cliente (antes de enviar) y en el servidor (422).
 *
 *   const f = useFormulario({ nombre: '', correo: '' }, { reglas: { nombre: ['requerido'], correo: ['requerido', 'email'] } });
 *   <input name="nombre" value={f.valores.nombre} onChange={(e) => f.set('nombre', e.target.value)}
 *          onBlur={() => f.validarUno('nombre')} aria-invalid={!!f.errores.nombre} />
 *   <form onSubmit={f.alEnviar((datos) => api.post('contacto', datos))} noValidate>
 */
export function useFormulario<T extends Record<string, unknown>>(inicial: T, opciones: OpcionesFormulario = {}) {
    const [valores, setValores] = useState<T>(inicial);
    const [errores, setErrores] = useState<Record<string, string>>({});
    const [enviando, setEnviando] = useState(false);

    const opcionesRef = useRef(opciones);
    opcionesRef.current = opciones;
    const valoresRef = useRef(valores);
    valoresRef.current = valores;
    const inicialRef = useRef(inicial);

    const set = useCallback(<K extends keyof T>(campo: K, valor: T[K]) => {
        setValores((actual) => ({ ...actual, [campo]: valor }));
        // Al corregir un campo se limpia su error.
        setErrores((actual) => {
            if (!actual[campo as string]) {
                return actual;
            }

            const resto = { ...actual };
            delete resto[campo as string];

            return resto;
        });
    }, []);

    /** Valida un campo al salir de él (onBlur) para dar retroalimentación inmediata. */
    const validarUno = useCallback((campo: keyof T & string) => {
        const { reglas, etiquetas } = opcionesRef.current;
        const lista = reglas?.[campo];

        if (!lista) {
            return;
        }

        const actuales = valoresRef.current;
        const mensaje = validarCampo(actuales[campo], lista, etiquetas?.[campo] ?? campo.replaceAll('_', ' '), actuales, campo);

        setErrores((e) => {
            const resto = { ...e };
            delete resto[campo];

            return mensaje ? { ...resto, [campo]: mensaje } : resto;
        });
    }, []);

    /** Valida todo el formulario en el cliente; devuelve true si no hay errores. */
    const validarCliente = useCallback((): boolean => {
        const { reglas, etiquetas } = opcionesRef.current;

        if (!reglas) {
            return true;
        }

        const encontrados = validar(valoresRef.current, reglas, etiquetas);
        setErrores(encontrados);

        if (Object.keys(encontrados).length > 0) {
            enfocarPrimerError(Object.keys(encontrados));

            return false;
        }

        return true;
    }, []);

    /** Valida en el cliente, envía y traduce los errores 422 del servidor a los campos. */
    const enviar = useCallback(
        async <R>(accion: (datos: T) => Promise<R>, opcionesEnvio: OpcionesEnvio<R> = {}): Promise<R | undefined> => {
            if (!validarCliente()) {
                toast.error('Revise los campos marcados en el formulario.');

                return undefined;
            }

            setEnviando(true);

            try {
                const resultado = await accion(valoresRef.current);
                const mensaje =
                    opcionesEnvio.mensajeExito === false
                        ? null
                        : (opcionesEnvio.mensajeExito ?? (resultado as { data?: { message?: string } } | undefined)?.data?.message ?? null);

                if (mensaje) {
                    toast.success(mensaje);
                }

                setErrores({});
                opcionesEnvio.onExito?.(resultado);

                return resultado;
            } catch (error) {
                const delServidor = erroresValidacion(error);

                if (Object.keys(delServidor).length > 0) {
                    setErrores(delServidor);
                    enfocarPrimerError(Object.keys(delServidor));
                }

                toast.error(mensajeError(error));

                return undefined;
            } finally {
                setEnviando(false);
            }
        },
        [validarCliente],
    );

    /** Atajo para onSubmit: previene el envío nativo del navegador y llama a enviar(). */
    const alEnviar = useCallback(
        <R>(accion: (datos: T) => Promise<R>, opcionesEnvio?: OpcionesEnvio<R>) =>
            (evento?: React.FormEvent) => {
                evento?.preventDefault();
                void enviar(accion, opcionesEnvio);
            },
        [enviar],
    );

    const reiniciar = useCallback((nuevos?: T) => {
        setValores(nuevos ?? inicialRef.current);
        setErrores({});
    }, []);

    return { valores, setValores, set, errores, setErrores, enviando, validarCliente, validarUno, enviar, alEnviar, reiniciar };
}

/** Lleva el foco al primer campo con error (accesibilidad). */
function enfocarPrimerError(campos: string[]): void {
    requestAnimationFrame(() => {
        for (const campo of campos) {
            const elemento =
                document.querySelector<HTMLElement>(`[name="${CSS.escape(campo)}"]`) ?? document.getElementById(campo);

            if (elemento) {
                elemento.focus();
                elemento.scrollIntoView({ block: 'center', behavior: 'smooth' });
                break;
            }
        }
    });
}
