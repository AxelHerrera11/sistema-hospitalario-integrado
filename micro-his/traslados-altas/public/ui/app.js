// PROTOTIPO (Semana 8): flujo consultar → decidir → completar → confirmar → procesar → resultado contra la API real.
import { interpretar, validarTraslado } from './mensajes.js';

const $ = (id) => document.getElementById(id);
let operacion = null; // 'traslado' | 'alta'

function mostrar(...visibles) {
    for (const id of ['estado', 'formulario', 'confirmacion']) $(id).hidden = !visibles.includes(id);
}

function anunciar({ tipo, texto, debeConsultar }) {
    $('resultado').className = tipo;
    $('resultado').textContent = texto;
    $('reconsultar').hidden = !debeConsultar;
}

async function llamar(metodo, ruta, cuerpo) {
    try {
        const respuesta = await fetch(ruta, { method: metodo, headers: { 'Content-Type': 'application/json', 'X-Actor-Ref': $('actor').value.trim() },
            body: cuerpo ? JSON.stringify(cuerpo) : undefined });
        return { estado: respuesta.status, cuerpo: await respuesta.json().catch(() => ({})) };
    } catch {
        // Sin respuesta: una lectura simplemente falló, pero un POST pudo haberse confirmado en el servidor → incierto.
        return metodo === 'POST' ? { estado: 500, cuerpo: { resultado: 'incierto' } } : { estado: 503, cuerpo: {} };
    }
}

async function consultar() {
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
    const operable = cuerpo.estado === 'open' && cuerpo.estancia_consultada_abierta;
    $('vacio').hidden = cuerpo.estancia_abierta_id !== null;
    $('acciones').hidden = !operable;
    mostrar('estado');
    anunciar({ tipo: '', texto: '' });
}

function elegir(tipo) {
    operacion = tipo;
    $('campos-traslado').hidden = tipo !== 'traslado';
    $('titulo-formulario').textContent = tipo === 'traslado' ? '3. Datos del traslado' : '3. Alta hospitalaria';
    mostrar('estado', 'formulario');
}

function revisar(evento) {
    evento.preventDefault();
    const datos = { cama_destino_id: $('cama').value, motivo: $('motivo').value };
    const invalido = operacion === 'traslado' ? validarTraslado(datos) : null;
    $('error-cama').hidden = invalido !== 'cama_destino_id';
    $('error-motivo').hidden = invalido !== 'motivo';
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
    const cuerpo = { estancia_esperada_id: $('estancia').value.trim(), ...(operacion === 'traslado' ? { cama_destino_id: $('cama').value.trim(), motivo: $('motivo').value.trim() } : {}) };
    const { estado, cuerpo: respuesta } = await llamar('POST', operacion === 'traslado' ? '/traslados' : '/altas', cuerpo);
    $('confirmar').disabled = false;
    $('confirmar').textContent = 'Confirmar';
    mostrar();
    anunciar(interpretar(estado, respuesta));
}

$('consulta').addEventListener('submit', (e) => { e.preventDefault(); consultar(); });
$('elegir-traslado').addEventListener('click', () => elegir('traslado'));
$('elegir-alta').addEventListener('click', () => elegir('alta'));
$('formulario').addEventListener('submit', revisar);
$('cancelar').addEventListener('click', () => mostrar('estado'));
$('volver').addEventListener('click', () => mostrar('estado', 'formulario'));
$('confirmar').addEventListener('click', confirmar);
$('reconsultar').addEventListener('click', consultar);
$('motivo').addEventListener('input', () => { $('contador').textContent = `${$('motivo').value.length} / 500`; });
