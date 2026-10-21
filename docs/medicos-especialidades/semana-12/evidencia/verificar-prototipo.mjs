#!/usr/bin/env node
/**
 * Verificación del prototipo de la Semana 11 (ASII-04) para la segunda evaluación parcial.
 *
 * Abre el prototipo en Chrome sin interfaz (CDP) y comprueba, sobre el archivo real:
 *   - los roles: qué ve el administrador, la recepcionista y el médico, y el 403;
 *   - los estados: vacío, médico sin horario y confirmación del alta;
 *   - los errores: 409 de licencia, 409 de principal única, conflicto de agenda,
 *     consulta sin respuesta y alta sin respuesta;
 *   - la accesibilidad: radiogroup con nombre, role="alert", role="status",
 *     foco visible de 3 px y objetivos táctiles de 44 px;
 *   - el tamaño de texto al 200 % (1.4.4) y el reflow a 640 y 320 px (1.4.10).
 *
 * Uso: node verificar-prototipo.mjs [--prototipo <ruta/index.html>] [--chrome <ruta>]
 * Salida: tabla Markdown por stdout y código de salida 1 si alguna comprobación no cumple.
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const aqui = dirname(fileURLToPath(import.meta.url));
const opcion = (nombre, porDefecto) => {
  const i = process.argv.indexOf(nombre);
  return i === -1 ? porDefecto : process.argv[i + 1];
};
const rutaPrototipo = resolve(
  opcion('--prototipo', join(aqui, '..', '..', 'semana-11', 'prototipo', 'index.html'))
);
const chrome = opcion('--chrome', 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe');
const PUERTO = 9333 + Math.floor(Math.random() * 500);
const dormir = (ms) => new Promise((r) => setTimeout(r, ms));

if (!existsSync(rutaPrototipo)) {
  console.error(`No se encontró el prototipo en ${rutaPrototipo}`);
  process.exit(2);
}
if (!existsSync(chrome)) {
  console.error(`No se encontró Chrome en ${chrome}`);
  process.exit(2);
}

/* ------------------------------------------------------------------ Chrome */

const perfil = mkdtempSync(join(tmpdir(), 'asii04-chrome-'));
const proceso = spawn(chrome, [
  '--headless=new',
  '--disable-gpu',
  '--no-first-run',
  '--no-default-browser-check',
  '--disable-extensions',
  `--remote-debugging-port=${PUERTO}`,
  `--user-data-dir=${perfil}`,
  'about:blank',
]);

async function esperarChrome() {
  for (let intento = 0; intento < 80; intento++) {
    try {
      const r = await fetch(`http://127.0.0.1:${PUERTO}/json/version`);
      if (r.ok) return (await r.json()).webSocketDebuggerUrl;
    } catch {
      /* el navegador todavía no escucha */
    }
    await dormir(250);
  }
  throw new Error('Chrome no abrió el puerto de depuración');
}

/* --------------------------------------------------------------------- CDP */

function crearCliente(ws) {
  const pendientes = new Map();
  const oyentes = new Map();
  let siguienteId = 1;
  ws.addEventListener('message', (evento) => {
    const mensaje = JSON.parse(evento.data);
    if (mensaje.id && pendientes.has(mensaje.id)) {
      const { resolver, rechazar } = pendientes.get(mensaje.id);
      pendientes.delete(mensaje.id);
      if (mensaje.error) rechazar(new Error(mensaje.error.message));
      else resolver(mensaje.result);
      return;
    }
    const fn = oyentes.get(`${mensaje.sessionId ?? ''}:${mensaje.method}`);
    if (fn) fn(mensaje.params);
  });
  return {
    enviar(method, params = {}, sessionId) {
      const id = siguienteId++;
      const cuerpo = { id, method, params };
      if (sessionId) cuerpo.sessionId = sessionId;
      ws.send(JSON.stringify(cuerpo));
      return new Promise((resolver, rechazar) => pendientes.set(id, { resolver, rechazar }));
    },
    alRecibir(metodo, sessionId, fn) {
      oyentes.set(`${sessionId ?? ''}:${metodo}`, fn);
    },
  };
}

/* ------------------------------------------------------- Página bajo prueba */

/** Ayudas disponibles dentro de la página para abreviar las expresiones. */
const PREAMBULO = `
  const botones = () => [...document.querySelectorAll('button')];
  const texto = () => document.body.innerText;
`;

/**
 * Cada navegación usa una URL única: si se repite la misma URL, Chrome hace una
 * navegación «same-document» y no vuelve a ejecutar el prototipo.
 */
let contador = 0;
function url(consulta) {
  contador += 1;
  const [base, fragmento] = consulta.split('#');
  const conContador = `${base}${base.includes('?') ? '&' : '?'}v=${contador}`;
  return `${pathToFileURL(rutaPrototipo).href}${conContador}${fragmento ? `#${fragmento}` : ''}`;
}

/** Comprobación: id, bloque, nombre, criterio, url, expresión, valor esperado y extras. */
const c = (id, bloque, nombre, criterio, url, expresion, esperado, extra = {}) => ({
  id,
  bloque,
  nombre,
  criterio,
  url,
  expresion,
  esperado,
  ...extra,
});

const COMPROBACIONES = [
  /* ---------------------------------------------------------------- roles */
  c('R1', 'Roles y permisos', 'El administrador ve «+ Registrar médico»', 'medicos.registrar', '?rol=admin#medicos', "botones().some((b) => b.textContent.includes('Registrar médico'))", true),
  c('R2', 'Roles y permisos', 'El administrador ve «Asociar especialidad»', 'medicos.asociar_especialidad', '?rol=admin#medicos', "texto().includes('Asociar especialidad')", true),
  c('R3', 'Roles y permisos', 'El administrador ve el número de colegiado', 'RNF-MED-04', '?rol=admin#medicos', "texto().includes('COL-9')", true),
  c('R4', 'Roles y permisos', 'La recepcionista no ve el alta de médicos', 'medicos.registrar', '?rol=recepcion#medicos', "!botones().some((b) => b.textContent.includes('Registrar médico'))", true),
  c('R5', 'Roles y permisos', 'La recepcionista no ve el número de colegiado', 'RNF-MED-04', '?rol=recepcion#medicos', "!texto().includes('COL-9')", true),
  c('R6', 'Roles y permisos', 'La recepcionista no ve «Asociar especialidad»', 'medicos.asociar_especialidad', '?rol=recepcion#medicos', "!texto().includes('Asociar especialidad')", true),
  c('R7', 'Roles y permisos', 'El médico ve «Mi disponibilidad» y no el filtro por especialidad', 'medicos.ver_disponibilidad', '?rol=medico#disponibilidad', "texto().includes('Mi disponibilidad') && !document.querySelector('#esp')", true),
  c('R8', 'Roles y permisos', 'Sin permiso: la recepcionista recibe el mensaje 403', 'RNF-MED-01', '?rol=recepcion#asociar', "texto().includes('No tiene permiso para esta acción')", true),
  /* -------------------------------------------------------------- estados */
  c('E1', 'Estados', 'Vacío: sin médicos vigentes para la especialidad', 'NO_DOCTORS_FOR_SPECIALTY', '?rol=recepcion#disponibilidad', "texto().includes('No hay médicos vigentes para esta especialidad')", true, { preparacion: "consulta = { esp: 'PED', fecha: '2026-10-19' }; pintar('disponibilidad');" }),
  c('E2', 'Estados', 'Médico sin horario visible, no oculto', 'SCHEDULE_NOT_CONFIGURED', '?rol=recepcion#disponibilidad', "texto().includes('Dr. Beto Ruiz') && texto().includes('Sin horario configurado')", true),
  c('E3', 'Estados', 'Alta confirmada: mensaje de éxito con enlace a la ficha', '201 Created', '?rol=admin&demo=alta&registrar#alta', "texto().includes('Médico registrado')", true, { espera: 800 }),
  c('E4', 'Estados', 'Elegir franja avisa que la reserva la confirma Citas', 'H2 · RF-MED-07', '?rol=recepcion#disponibilidad', "texto().includes('Elegir no reserva')", true, { preparacion: "document.querySelector('input[name=\"franja\"]').checked = true; elegir();", espera: 800 }),
  /* -------------------------------------------------------------- errores */
  c('X1', 'Errores', '409 de licencia repetida en el alta', 'LICENSE_ALREADY_REGISTERED', '?rol=admin&sim=licencia&demo=alta', "texto().includes('Ese número de colegiado ya está registrado') && texto().includes('LICENSE_ALREADY_REGISTERED')", true, { espera: 800 }),
  c('X2', 'Errores', 'Alta sin respuesta: pide buscar la licencia antes de repetir', 'H1 · idempotencia del alta', '?rol=admin&sim=sin-respuesta&demo=alta', "texto().includes('No sabemos si se guardó') && texto().includes('Buscar licencia')", true, { espera: 800 }),
  c('X3', 'Errores', '409 de principal única al asociar especialidad', 'PRIMARY_SPECIALTY_CONFLICT', '?rol=admin&demo=asociar&principal', "texto().includes('Ya tiene una principal en ese periodo') && texto().includes('PRIMARY_SPECIALTY_CONFLICT')", true, { espera: 800 }),
  c('X4', 'Errores', 'Error crítico: la franja tomada avisa, muestra el trace_id y desaparece de la lista', 'Conflicto de agenda', '?rol=recepcion&sim=tomada#disponibilidad', "texto().includes('ya no está libre') && texto().includes('trace_id') && document.querySelectorAll('input[name=\"franja\"]').length === 1", true, { preparacion: "document.querySelector('input[name=\"franja\"]').checked = true; elegir();", espera: 800 }),
  c('X5', 'Errores', 'Sin conexión: banda visible, franjas y acción principal deshabilitadas', 'RNF-MED-05', '?rol=recepcion&sim=sin-conexion#disponibilidad', "texto().includes('Sin conexión') && [...document.querySelectorAll('input[name=\"franja\"]')].every((i) => i.disabled) && botones().some((b) => b.textContent.includes('Elegir franja') && b.disabled)", true),
  c('X6', 'Errores', 'Consulta sin respuesta: reintento manual, sin reintentos infinitos', 'Cliente HTTP de la Semana 5', '?rol=recepcion&sim=sin-respuesta#disponibilidad', "texto().includes('No se pudo consultar') && texto().includes('Reintentar')", true, { preparacion: "document.querySelector('input[name=\"franja\"]').checked = true; elegir();", espera: 800 }),
  /* ------------------------------------------------------- accesibilidad */
  c('A1', 'Accesibilidad', 'Las franjas son un radiogroup con nombre accesible por médico', '1.3.1 · H3', '?rol=recepcion#disponibilidad', "document.querySelector('fieldset[role=\"radiogroup\"]')?.getAttribute('aria-label') ?? ''", 'Franjas de Dra. Ana López'),
  c('A2', 'Accesibilidad', 'El mensaje de error se anuncia con role="alert"', '4.1.3 · H2', '?rol=recepcion&sim=tomada#disponibilidad', "document.querySelector('[role=\"alert\"]')?.innerText.includes('ya no está libre') ?? false", true, { preparacion: "document.querySelector('input[name=\"franja\"]').checked = true; elegir();", espera: 800 }),
  c('A3', 'Accesibilidad', 'El estado de la pantalla se anuncia con role="status"', '4.1.3', '?rol=recepcion#disponibilidad', "document.getElementById('estado')?.getAttribute('role') ?? ''", 'status'),
  c('A4', 'Accesibilidad', 'El foco visible tiene contorno de 3 px al recorrer con Tab', '2.4.7 · H9', '?rol=recepcion#disponibilidad', 'getComputedStyle(document.activeElement).outlineWidth', '3px', { teclas: ['Tab', 'Tab'] }),
  c('A5', 'Accesibilidad', 'La franja enfocada muestra el contorno de foco', '2.4.7 · H9', '?rol=recepcion#disponibilidad', "(() => { const el = document.activeElement; if (!el.closest('label')) return 'sin-franja'; return getComputedStyle(el.closest('label')).outlineWidth; })()", '3px', { teclas: ['Tab', 'Tab', 'Tab', 'Tab', 'Tab', 'Tab', 'Tab', 'Tab'] }),
  c('A6', 'Accesibilidad', 'Los objetivos táctiles miden al menos 44 px', '2.5.5 · Semana 10', '?rol=recepcion#disponibilidad', "[...document.querySelectorAll('button, .franjas label')].every((el) => el.getBoundingClientRect().height >= 44)", true),
  /* -------------------------------------------------- tamaño y reflow */
  c('T1', 'Tamaño de texto y reflow', 'Al 200 % del tamaño de texto no hay desborde horizontal (1.4.4)', '1.4.4', '?rol=recepcion#disponibilidad', "document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1 && document.querySelector('fieldset[role=\"radiogroup\"]') !== null", true, { preparacion: "document.documentElement.style.fontSize = '32px';", espera: 200 }),
  c('T2', 'Tamaño de texto y reflow', 'A 640 px (200 % de una pantalla de 1280) no hay desborde horizontal (1.4.10)', '1.4.10', '?rol=recepcion#disponibilidad', 'document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1', true, { viewport: [640, 800] }),
  c('T3', 'Tamaño de texto y reflow', 'A 320 px no hay desborde horizontal y la acción principal sigue visible', 'Semana 10 · 320–430 px', '?rol=recepcion#disponibilidad', "(() => { const b = botones().find((x) => x.textContent.includes('Elegir franja')); const r = b.getBoundingClientRect(); return document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1 && r.height > 0 && r.bottom <= window.innerHeight + 1; })()", true, { viewport: [320, 640] }),
];

/* ------------------------------------------------------------------ corrida */

const ws = new WebSocket(await esperarChrome());
await new Promise((resolver, rechazar) => {
  ws.addEventListener('open', resolver, { once: true });
  ws.addEventListener('error', () => rechazar(new Error('No se pudo conectar con Chrome')), { once: true });
});
const cliente = crearCliente(ws);
const { targetId } = await cliente.enviar('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await cliente.enviar('Target.attachToTarget', { targetId, flatten: true });
await cliente.enviar('Page.enable', {}, sessionId);
await cliente.enviar('Runtime.enable', {}, sessionId);

const viewport = (ancho, alto) =>
  cliente.enviar('Emulation.setDeviceMetricsOverride', { width: ancho, height: alto, deviceScaleFactor: 1, mobile: false }, sessionId);

async function navegar(destino) {
  await Promise.race([
    new Promise((resolver) => {
      cliente.alRecibir('Page.loadEventFired', sessionId, resolver);
      cliente.enviar('Page.navigate', { url: destino }, sessionId);
    }),
    dormir(10000).then(() => {
      throw new Error(`La navegación a ${destino} no terminó de cargar`);
    }),
  ]);
  await dormir(250);
}

async function evaluar(expresion) {
  const r = await cliente.enviar(
    'Runtime.evaluate',
    { expression: `(function () { ${PREAMBULO} return (${expresion}); })()`, returnByValue: true, awaitPromise: true },
    sessionId
  );
  if (r.exceptionDetails) throw new Error(r.exceptionDetails.exception?.description ?? 'error de evaluación');
  return r.result.value;
}

async function ejecutar(sentencias) {
  const r = await cliente.enviar(
    'Runtime.evaluate',
    { expression: `(function () { ${sentencias} })()`, returnByValue: true, awaitPromise: true },
    sessionId
  );
  if (r.exceptionDetails) throw new Error(r.exceptionDetails.exception?.description ?? 'error de evaluación');
  return r.result.value;
}

async function pulsarTab() {
  for (const type of ['keyDown', 'keyUp']) {
    await cliente.enviar(
      'Input.dispatchKeyEvent',
      { type, key: 'Tab', code: 'Tab', windowsVirtualKeyCode: 9, nativeVirtualKeyCode: 9 },
      sessionId
    );
  }
  await dormir(80);
}

const resultados = [];
for (const comprobacion of COMPROBACIONES) {
  await viewport(comprobacion.viewport?.[0] ?? 1280, comprobacion.viewport?.[1] ?? 800);
  await navegar(url(comprobacion.url));
  if (comprobacion.preparacion) await ejecutar(comprobacion.preparacion);
  for (const _ of comprobacion.teclas ?? []) await pulsarTab();
  if (comprobacion.espera) await dormir(comprobacion.espera);
  const valor = await evaluar(comprobacion.expresion);
  const cumple = String(valor) === String(comprobacion.esperado);
  resultados.push({ ...comprobacion, valor, cumple });
  console.error(`${cumple ? 'ok  ' : 'FALLA'} ${comprobacion.id} ${comprobacion.nombre}`);
}

const fallan = resultados.filter((r) => !r.cumple);
console.log(
  [
    '| # | Bloque | Comprobación | Criterio | Resultado | Valor observado |',
    '|---|---|---|---|---|---|',
    ...resultados.map(
      (r) => `| ${r.id} | ${r.bloque} | ${r.nombre} | ${r.criterio} | ${r.cumple ? 'Cumple' : '**No cumple**'} | \`${String(r.valor)}\` |`
    ),
    '',
    `Ejecutadas ${resultados.length} comprobaciones sobre \`${rutaPrototipo}\` en Chrome sin interfaz: ${resultados.length - fallan.length} cumplen y ${fallan.length} no cumplen.`,
  ].join('\n')
);

ws.close();
proceso.kill();
await dormir(500);
try {
  rmSync(perfil, { recursive: true, force: true });
} catch {
  /* Chrome todavía mantiene algún archivo del perfil; el sistema lo limpia después */
}
process.exit(fallan.length > 0 ? 1 : 0);
