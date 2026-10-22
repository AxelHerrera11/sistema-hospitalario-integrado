// PROTOTIPO (Semanas 8 y 10): flujo consultar → decidir → completar → confirmar → procesar → resultado contra la API real.
import { interpretar, validarConsulta, validarTraslado } from './mensajes.js';

const $ = (id) => document.getElementById(id);
const ESPERA_MS = 15000; // conexión limitada: más allá de este tiempo se deja de esperar la respuesta
let operacion = null; // 'traslado' | 'alta'
let estanciaNueva = null;

function mostrar(...visibles) {
    for (const id of ['estado', 'formulario', 'confirmacion']) $(id).hidden = !visibles.includes(id);
}

function anunciar({ tipo, texto, debeConsultar }) {
    $('resultado').className = tipo;
    $('resultado').textContent = texto;
    $('reconsultar').hidden = !debeConsultar;
}

function marcar(campo, invalido) {
    $(campo).setAttribute('aria-invalid', String(invalido));
}

async function llamar(metodo, ruta, cuerpo) {
    if (!navigator.onLine) return { estado: 0, cuerpo: { motivo: 'sin_red' } }; // no se envía nada sin red
    const corte = new AbortController();
    const reloj = setTimeout(() => corte.abort(), ESPERA_MS);
    try {
        const respuesta = await fetch(ruta, { method: metodo, signal: corte.signal,
            headers: { 'Content-Type': 'application/json', 'X-Actor-Ref': $('actor').value.trim() }, body: cuerpo ? JSON.stringify(cuerpo) : undefined });
        return { estado: respuesta.status, cuerpo: await respuesta.json().catch(() => ({})) };
    } catch {
        // Sin respuesta: una lectura simplemente falló, pero un POST pudo haberse confirmado en el servidor → incierto.
        return metodo === 'POST' ? { estado: 0, cuerpo: { motivo: 'tiempo_agotado' } } : { estado: 503, cuerpo: {} };
    } finally {
        clearTimeout(reloj);
    }
}

async function consultar() {
    const invalido = validarConsulta({ actor: $('actor').value, estancia: $('estancia').value });
    marcar('actor', invalido === 'actor');
    marcar('estancia', invalido === 'estancia');
    if (invalido) {
        mostrar();
        anunciar({ tipo: 'invalida', texto: invalido === 'actor' ? 'Indique una referencia de usuario válida.' : 'Indique una estancia válida.' });
        return $(invalido).focus();
    }
    anunciar({ tipo: '', texto: 'Consultando…' });
    const { estado, cuerpo } = await llamar('GET', `/estancias/${encodeURIComponent($('estancia').value.trim())}/internacion`);
    if (estado !== 200) {
        mostrar();
        return anunciar(estado === 404 ? { tipo: 'rechazo', texto: 'La estancia no existe.' } : interpretar(estado, cuerpo));
    }
    $('datos').innerHTML = '';
    for (const [clave, valor] of [['Episodio', cuerpo.estado === 'open' ? 'abierto' : 'cerrado'], ['Cama actual', cuerpo.cama_actual_id ?? '—'],
        ['Estancia vigente', cuerpo.estancia_consultada_abierta ? 'sí' : 'no (los datos cambiaron)']]) {
        $('datos').append(Object.assign(document.createElement('dt'), { textContent: clave }), Object.assign(document.createElement('dd'), { textContent: valor }));
    }
    $('vacio').hidden = cuerpo.estancia_abierta_id !== null;
    $('acciones').hidden = !(cuerpo.estado === 'open' && cuerpo.estancia_consultada_abierta);
    mostrar('estado');
    anunciar({ tipo: '', texto: 'Situación actualizada.' }); // H2: se anuncia y el foco va a la nueva sección
    $('titulo-estado').focus();
}

function elegir(tipo) {
    operacion = tipo;
    $('campos-traslado').hidden = tipo !== 'traslado';
    $('cama').required = $('motivo').required = tipo === 'traslado';
    $('titulo-formulario').textContent = tipo === 'traslado' ? '3. Datos del traslado' : '3. Alta hospitalaria';
    mostrar('estado', 'formulario');
}

function revisar(evento) {
    evento.preventDefault();
    const datos = { cama_destino_id: $('cama').value, motivo: $('motivo').value };
    const invalido = operacion === 'traslado' ? validarTraslado(datos) : null;
    $('error-cama').hidden = invalido !== 'cama_destino_id';
    $('error-motivo').hidden = invalido !== 'motivo';
    marcar('cama', invalido === 'cama_destino_id'); // H3
    marcar('motivo', invalido === 'motivo');
    if (invalido) return $(invalido === 'motivo' ? 'motivo' : 'cama').focus();
    $('resumen').textContent = operacion === 'traslado'
        ? `Trasladar la estancia ${$('estancia').value.trim()} a la cama ${datos.cama_destino_id.trim()}. Motivo: ${datos.motivo.trim()}`
        : `Dar de alta: se cerrará la estancia ${$('estancia').value.trim()} y el episodio.`;
    mostrar('estado', 'confirmacion');
    $('confirmar').focus();
}

async function confirmar() {
    $('confirmar').disabled = true;
    $('confirmar').textContent = 'Procesando…';
    anunciar({ tipo: '', texto: 'Procesando…' }); // el estado de carga también se anuncia, no solo se ve en el botón
    const cuerpo = { estancia_esperada_id: $('estancia').value.trim(), ...(operacion === 'traslado' ? { cama_destino_id: $('cama').value.trim(), motivo: $('motivo').value.trim() } : {}) };
    const { estado, cuerpo: respuesta } = await llamar('POST', operacion === 'traslado' ? '/traslados' : '/altas', cuerpo);
    $('confirmar').disabled = false;
    $('confirmar').textContent = 'Confirmar';
    mostrar();
    const mensaje = interpretar(estado, respuesta);
    if (mensaje.tipo === 'exito') { // H7: nada queda cargado para la próxima operación
        $('formulario').reset();
        $('contador').textContent = '0 / 500';
    }
    estanciaNueva = mensaje.estanciaNueva ?? null;
    $('seguir').hidden = estanciaNueva === null;
    anunciar(mensaje);
}

$('consulta').addEventListener('submit', (e) => { e.preventDefault(); consultar(); });
$('elegir-traslado').addEventListener('click', () => elegir('traslado'));
$('elegir-alta').addEventListener('click', () => elegir('alta'));
$('formulario').addEventListener('submit', revisar);
// Semana 12: «Cancelar» y «Volver» devuelven el foco al paso anterior, que antes quedaba sobre un elemento oculto.
function cancelar() {
    mostrar('estado');
    $('titulo-estado').focus();
}
function volver() {
    mostrar('estado', 'formulario');
    $(operacion === 'traslado' ? 'cama' : 'titulo-formulario').focus();
}
$('cancelar').addEventListener('click', cancelar);
$('volver').addEventListener('click', volver);
// Escape retrocede un paso (confirmación → datos → situación), salvo mientras se procesa un envío.
document.addEventListener('keydown', (evento) => {
    if (evento.key !== 'Escape' || $('confirmar').disabled) return;
    if (!$('confirmacion').hidden) volver();
    else if (!$('formulario').hidden) cancelar();
});
$('confirmar').addEventListener('click', confirmar);
$('reconsultar').addEventListener('click', consultar);
$('seguir').addEventListener('click', () => { $('estancia').value = estanciaNueva; $('seguir').hidden = true; consultar(); });
$('motivo').addEventListener('input', () => { $('contador').textContent = `${$('motivo').value.length} / 500`; });
