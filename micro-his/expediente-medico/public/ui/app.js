// Lógica de aplicación del prototipo: eventos, validación previa y llamadas al API del micro-HIS.
// Las reglas de acceso las decide SIEMPRE el backend; aquí solo se evita enviar entradas inválidas.
import { debeOcultar, interpretar, siguienteReintento, validarCodigo } from './estados.js';
import { limpiarExpediente, pintarEstado, pintarExpediente } from './vista.js';

const $ = (id) => document.getElementById(id);
const estado = $('estado');
const expediente = $('expediente');
const botones = [$('consultar'), $('abrir')];
let intentos = 0;
let codigoMostrado = null; // código del expediente que está en pantalla, o null

function contexto() {
    return { usuario: $('usuario').value, hospital: $('hospital').value };
}

/** Valida el código antes de enviar; marca el campo y deja el foco en él si no es válido. */
function codigoValido() {
    const resultado = validarCodigo($('codigo').value);
    const campo = $('codigo');
    campo.setAttribute('aria-invalid', String(!resultado.valido));
    $('error-codigo').textContent = resultado.mensaje;
    $('error-codigo').hidden = resultado.valido;
    if (!resultado.valido) {
        // H-01: nunca dejar el expediente de otro paciente junto a un código distinto.
        limpiarExpediente(expediente);
        codigoMostrado = null;
        pintarEstado(estado, interpretar('consultar', 422, { code: 'CODIGO_PACIENTE_INVALIDO' }));
        campo.focus();
    }
    return resultado.valido ? resultado.codigo : null;
}

async function pedir(operacion, codigo) {
    const { usuario, hospital } = contexto();
    try {
        const respuesta = await fetch(`/patients/${encodeURIComponent(codigo)}/medical-record`, {
            method: operacion === 'abrir' ? 'POST' : 'GET',
            headers: { Accept: 'application/json', 'X-User-ID': usuario, 'X-Tenant-ID': hospital },
            signal: AbortSignal.timeout(10000),
        });
        return { estado: respuesta.status, cuerpo: await respuesta.json().catch(() => ({})) };
    } catch {
        return { estado: 0, cuerpo: {} };
    }
}

async function ejecutar(operacion, codigo, esReintento = false) {
    intentos = esReintento ? intentos + 1 : 0;
    limpiarExpediente(expediente);
    codigoMostrado = null;
    botones.forEach((b) => { b.disabled = true; });
    estado.parentElement.setAttribute('aria-busy', 'true');
    pintarEstado(estado, { tipo: 'cargando', titulo: 'Cargando…', mensaje: operacion === 'abrir' ? 'Abriendo el expediente.' : 'Consultando el expediente.' });

    const { estado: codigoHttp, cuerpo } = await pedir(operacion, codigo);
    const resultado = interpretar(operacion, codigoHttp, cuerpo);
    botones.forEach((b) => { b.disabled = false; });
    estado.parentElement.setAttribute('aria-busy', 'false');

    const acciones = [];
    if (resultado.puedeReintentar) {
        const siguiente = siguienteReintento(intentos);
        resultado.aviso = siguiente.aviso;
        if (siguiente.permitido) acciones.push({ texto: 'Reintentar', alPulsar: () => ejecutar(operacion, codigo, true) });
    }
    if (cuerpo?.code === 'EXPEDIENTE_NO_ABIERTO') acciones.push({ texto: 'Solicitar apertura…', alPulsar: () => confirmarApertura(codigo) });
    if (operacion === 'abrir' && resultado.tipo === 'exito') acciones.push({ texto: 'Consultar expediente', alPulsar: () => ejecutar('consultar', codigo) });
    pintarEstado(estado, resultado, acciones);
    if (operacion === 'consultar' && resultado.datos) {
        pintarExpediente(expediente, resultado.datos, ocultar);
        codigoMostrado = codigo;
    }
    $('t-resultado').focus();
}

function ocultar() {
    limpiarExpediente(expediente);
    codigoMostrado = null;
    pintarEstado(estado, { tipo: 'inicial', titulo: 'Datos ocultos', mensaje: 'Se quitaron de la pantalla los datos del paciente.' });
    $('codigo').focus();
}

function confirmarApertura(codigo) {
    $('c-codigo').textContent = codigo;
    $('c-hospital').textContent = $('hospital').selectedOptions[0].textContent;
    $('confirmacion').showModal();
    $('cancelar').focus();
}

$('formulario').addEventListener('submit', (evento) => {
    evento.preventDefault();
    const codigo = codigoValido();
    if (codigo) ejecutar('consultar', codigo);
});
$('abrir').addEventListener('click', () => {
    const codigo = codigoValido();
    if (codigo) confirmarApertura(codigo);
});
$('cancelar').addEventListener('click', () => $('confirmacion').close());
$('confirmar').addEventListener('click', () => {
    $('confirmacion').close();
    ejecutar('abrir', $('c-codigo').textContent);
});
// Editar el código: si ya no coincide con el expediente mostrado, se quita de la pantalla (H-01).
$('codigo').addEventListener('input', () => {
    if (debeOcultar(codigoMostrado, $('codigo').value)) ocultar();
});
// Cambiar de usuario o de hospital quita de la pantalla lo que vio la identidad anterior.
for (const id of ['usuario', 'hospital']) {
    $(id).addEventListener('change', () => {
        if (!expediente.hidden) ocultar();
    });
}
