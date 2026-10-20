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
 * Uso:  node verificar-prototipo.mjs [--prototipo <ruta/index.html>] [--chrome <ruta>]
 * Salida: tabla Markdown por stdout y código de salida 1 si alguna comprobación falla.
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const aqui = dirname(fileURLToPath(import.meta.url));

function opcion(nombre, porDefecto) {
  const i = process.argv.indexOf(nombre);
  return i === -1 ? porDefecto : process.argv[i + 1];
}

const rutaPrototipo = resolve(
  opcion('--prototipo', join(aqui, '..', '..', 'semana-11', 'prototipo', 'index.html'))
);
const chrome = opcion('--chrome', 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe');
const PUERTO = 9333 + Math.floor(Math.random() * 500);

if (!existsSync(rutaPrototipo)) {
  console.error(`No se encontró el prototipo en ${rutaPrototipo}`);
  process.exit(2);
}
if (!existsSync(chrome)) {
  console.error(`No se encontró Chrome en ${chrome}`);
  process.exit(2);
}

const dormir = (ms) => new Promise((r) => setTimeout(r, ms));

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
    const clave = `${mensaje.sessionId ?? ''}:${mensaje.method}`;
    const fn = oyentes.get(clave);
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

/**
 * Cada navegación usa una URL única: si se repite la misma URL, Chrome hace una
 * navegación «same-document» y no vuelve a ejecutar el prototipo.
 */
let contador = 0;
function url(consulta) {
  contador += 1;
  const [base, fragmento] = consulta.split('#');
  const separador = base.includes('?') ? '&' : '?';
  const conContador = `${base}${separador}v=${contador}`;
  return `${pathToFileURL(rutaPrototipo).href}${conContador}${fragmento ? `#${fragmento}` : ''}`;
}

const COMPROBACIONES = [
  /* ---------------------------------------------------------------- roles */
  {
    id: 'R1',
    bloque: 'Roles y permisos',
    nombre: 'El administrador ve «+ Registrar médico»',
    criterio: 'medicos.registrar',
    url: '?rol=admin#medicos',
    expresion: `[...document.querySelectorAll('button')].some((b) => b.textContent.includes('Registrar médico'))`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'R2',
    bloque: 'Roles y permisos',
    nombre: 'El administrador ve «Asociar especialidad»',
    criterio: 'medicos.asociar_especialidad',
    url: '?rol=admin#medicos',
    expresion: `document.body.innerText.includes('Asociar especialidad')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'R3',
    bloque: 'Roles y permisos',
    nombre: 'El administrador ve el número de colegiado',
    criterio: 'RNF-MED-04',
    url: '?rol=admin#medicos',
    expresion: `document.body.innerText.includes('COL-9')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'R4',
    bloque: 'Roles y permisos',
    nombre: 'La recepcionista no ve el alta de médicos',
    criterio: 'medicos.registrar',
    url: '?rol=recepcion#medicos',
    expresion: `![...document.querySelectorAll('button')].some((b) => b.textContent.includes('Registrar médico'))`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'R5',
    bloque: 'Roles y permisos',
    nombre: 'La recepcionista no ve el número de colegiado',
    criterio: 'RNF-MED-04',
    url: '?rol=recepcion#medicos',
    expresion: `!document.body.innerText.includes('COL-9')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'R6',
    bloque: 'Roles y permisos',
    nombre: 'La recepcionista no ve «Asociar especialidad»',
    criterio: 'medicos.asociar_especialidad',
    url: '?rol=recepcion#medicos',
    expresion: `!document.body.innerText.includes('Asociar especialidad')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'R7',
    bloque: 'Roles y permisos',
    nombre: 'El médico ve «Mi disponibilidad» y no el filtro por especialidad',
    criterio: 'medicos.ver_disponibilidad',
    url: '?rol=medico#disponibilidad',
    expresion: `document.body.innerText.includes('Mi disponibilidad') && !document.querySelector('#esp')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'R8',
    bloque: 'Roles y permisos',
    nombre: 'Sin permiso: la recepcionista recibe el mensaje 403',
    criterio: 'RNF-MED-01',
    url: '?rol=recepcion#asociar',
    expresion: `document.body.innerText.includes('No tiene permiso para esta acción')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },

  /* -------------------------------------------------------------- estados */
  {
    id: 'E1',
    bloque: 'Estados',
    nombre: 'Vacío: sin médicos vigentes para la especialidad',
    criterio: 'NO_DOCTORS_FOR_SPECIALTY',
    url: '?rol=recepcion#disponibilidad',
    preparacion: `consulta = { esp: 'PED', fecha: '2026-10-19' }; pintar('disponibilidad');`,
    expresion: `document.body.innerText.includes('No hay médicos vigentes para esta especialidad')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'E2',
    bloque: 'Estados',
    nombre: 'Médico sin horario visible, no oculto',
    criterio: 'SCHEDULE_NOT_CONFIGURED',
    url: '?rol=recepcion#disponibilidad',
    expresion: `document.body.innerText.includes('Dr. Beto Ruiz') && document.body.innerText.includes('Sin horario configurado')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'E3',
    bloque: 'Estados',
    nombre: 'Alta confirmada: mensaje de éxito con enlace a la ficha',
    criterio: '201 Created',
    url: '?rol=admin&demo=alta&registrar#alta',
    espera: 800,
    expresion: `document.body.innerText.includes('Médico registrado')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'E4',
    bloque: 'Estados',
    nombre: 'Elegir franja avisa que la reserva la confirma Citas',
    criterio: 'H2 · RF-MED-07',
    url: '?rol=recepcion#disponibilidad',
    preparacion: `document.querySelector('input[name="franja"]').checked = true; elegir();`,
    espera: 800,
    expresion: `document.body.innerText.includes('Elegir no reserva')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },

  /* -------------------------------------------------------------- errores */
  {
    id: 'X1',
    bloque: 'Errores',
    nombre: '409 de licencia repetida en el alta',
    criterio: 'LICENSE_ALREADY_REGISTERED',
    url: '?rol=admin&sim=licencia&demo=alta',
    espera: 800,
    expresion: `document.body.innerText.includes('Ese número de colegiado ya está registrado') && document.body.innerText.includes('LICENSE_ALREADY_REGISTERED')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'X2',
    bloque: 'Errores',
    nombre: 'Alta sin respuesta: pide buscar la licencia antes de repetir',
    criterio: 'H1 · idempotencia del alta',
    url: '?rol=admin&sim=sin-respuesta&demo=alta',
    espera: 800,
    expresion: `document.body.innerText.includes('No sabemos si se guardó') && document.body.innerText.includes('Buscar licencia')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'X3',
    bloque: 'Errores',
    nombre: '409 de principal única al asociar especialidad',
    criterio: 'PRIMARY_SPECIALTY_CONFLICT',
    url: '?rol=admin&demo=asociar&principal',
    espera: 800,
    expresion: `document.body.innerText.includes('Ya tiene una principal en ese periodo') && document.body.innerText.includes('PRIMARY_SPECIALTY_CONFLICT')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'X4',
    bloque: 'Errores',
    nombre: 'Error crítico: la franja tomada avisa, muestra el trace_id y desaparece de la lista',
    criterio: 'Conflicto de agenda',
    url: '?rol=recepcion&sim=tomada#disponibilidad',
    preparacion: `document.querySelector('input[name="franja"]').checked = true; elegir();`,
    espera: 800,
    expresion: `document.body.innerText.includes('ya no está libre') && document.body.innerText.includes('trace_id') && document.querySelectorAll('input[name="franja"]').length === 1`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'X5',
    bloque: 'Errores',
    nombre: 'Sin conexión: banda visible, franjas y acción principal deshabilitadas',
    criterio: 'RNF-MED-05',
    url: '?rol=recepcion&sim=sin-conexion#disponibilidad',
    expresion: `document.body.innerText.includes('Sin conexión') && [...document.querySelectorAll('input[name="franja"]')].every((i) => i.disabled) && [...document.querySelectorAll('button')].some((b) => b.textContent.includes('Elegir franja') && b.disabled)`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'X6',
    bloque: 'Errores',
    nombre: 'Consulta sin respuesta: reintento manual, sin reintentos infinitos',
    criterio: 'Cliente HTTP de la Semana 5',
    url: '?rol=recepcion&sim=sin-respuesta#disponibilidad',
    preparacion: `document.querySelector('input[name="franja"]').checked = true; elegir();`,
    espera: 800,
    expresion: `document.body.innerText.includes('No se pudo consultar') && document.body.innerText.includes('Reintentar')`,
    esperado: 'true',
    coincide: (v) => v === true,
  },

  /* ------------------------------------------------------- accesibilidad */
  {
    id: 'A1',
    bloque: 'Accesibilidad',
    nombre: 'Las franjas son un radiogroup con nombre accesible por médico',
    criterio: '1.3.1 · H3',
    url: '?rol=recepcion#disponibilidad',
    expresion: `document.querySelector('fieldset[role="radiogroup"]')?.getAttribute('aria-label') ?? ''`,
    esperado: 'Franjas de Dra. Ana López',
    coincide: (v) => v === 'Franjas de Dra. Ana López',
  },
  {
    id: 'A2',
    bloque: 'Accesibilidad',
    nombre: 'El mensaje de error se anuncia con role="alert"',
    criterio: '4.1.3 · H2',
    url: '?rol=recepcion&sim=tomada#disponibilidad',
    preparacion: `document.querySelector('input[name="franja"]').checked = true; elegir();`,
    espera: 800,
    expresion: `document.querySelector('[role="alert"]')?.innerText.includes('ya no está libre') ?? false`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'A3',
    bloque: 'Accesibilidad',
    nombre: 'El estado de la pantalla se anuncia con role="status"',
    criterio: '4.1.3',
    url: '?rol=recepcion#disponibilidad',
    expresion: `document.getElementById('estado')?.getAttribute('role') ?? ''`,
    esperado: 'status',
    coincide: (v) => v === 'status',
  },
  {
    id: 'A4',
    bloque: 'Accesibilidad',
    nombre: 'El foco visible tiene contorno de 3 px al recorrer con Tab',
    criterio: '2.4.7 · H9',
    url: '?rol=recepcion#disponibilidad',
    teclas: ['Tab', 'Tab'],
    expresion: `getComputedStyle(document.activeElement).outlineWidth`,
    esperado: '3px',
    coincide: (v) => v === '3px',
  },
  {
    id: 'A5',
    bloque: 'Accesibilidad',
    nombre: 'La franja enfocada muestra el contorno de foco',
    criterio: '2.4.7 · H9',
    url: '?rol=recepcion#disponibilidad',
    teclas: ['Tab', 'Tab', 'Tab', 'Tab', 'Tab', 'Tab', 'Tab', 'Tab'],
    expresion: `(() => { const el = document.activeElement; if (!el.closest('label')) return 'sin-franja'; return getComputedStyle(el.closest('label')).outlineWidth; })()`,
    esperado: '3px',
    coincide: (v) => v === '3px',
  },
  {
    id: 'A6',
    bloque: 'Accesibilidad',
    nombre: 'Los objetivos táctiles miden al menos 44 px',
    criterio: '2.5.5 · Semana 10',
    url: '?rol=recepcion#disponibilidad',
    expresion: `[...document.querySelectorAll('button, .franjas label')].every((el) => el.getBoundingClientRect().height >= 44)`,
    esperado: 'true',
    coincide: (v) => v === true,
  },

  /* -------------------------------------------------- tamaño y reflow */
  {
    id: 'T1',
    bloque: 'Tamaño de texto y reflow',
    nombre: 'Al 200 % del tamaño de texto no hay desborde horizontal (1.4.4)',
    criterio: '1.4.4',
    url: '?rol=recepcion#disponibilidad',
    preparacion: `document.documentElement.style.fontSize = '32px';`,
    espera: 200,
    expresion: `document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1 && document.querySelector('fieldset[role="radiogroup"]') !== null`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'T2',
    bloque: 'Tamaño de texto y reflow',
    nombre: 'A 640 px (200 % de una pantalla de 1280) no hay desborde horizontal (1.4.10)',
    criterio: '1.4.10',
    url: '?rol=recepcion#disponibilidad',
    viewport: [640, 800],
    expresion: `document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
  {
    id: 'T3',
    bloque: 'Tamaño de texto y reflow',
    nombre: 'A 320 px no hay desborde horizontal y la acción principal sigue visible',
    criterio: 'Semana 10 · 320–430 px',
    url: '?rol=recepcion#disponibilidad',
    viewport: [320, 640],
    expresion: `(() => { const b = [...document.querySelectorAll('button')].find((x) => x.textContent.includes('Elegir franja')); const r = b.getBoundingClientRect(); return document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1 && r.height > 0 && r.bottom <= window.innerHeight + 1; })()`,
    esperado: 'true',
    coincide: (v) => v === true,
  },
];

/* ------------------------------------------------------------------ corrida */

const wsUrl = await esperarChrome();
const ws = new WebSocket(wsUrl);
await new Promise((resolver, rechazar) => {
  ws.addEventListener('open', resolver, { once: true });
  ws.addEventListener('error', () => rechazar(new Error('No se pudo conectar con Chrome')), { once: true });
});

const cliente = crearCliente(ws);
const { targetId } = await cliente.enviar('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await cliente.enviar('Target.attachToTarget', { targetId, flatten: true });
await cliente.enviar('Page.enable', {}, sessionId);
await cliente.enviar('Runtime.enable', {}, sessionId);
await cliente.enviar(
  'Emulation.setDeviceMetricsOverride',
  { width: 1280, height: 800, deviceScaleFactor: 1, mobile: false },
  sessionId
);

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
    { expression: `(function () { return (${expresion}); })()`, returnByValue: true, awaitPromise: true },
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
  await cliente.enviar(
    'Emulation.setDeviceMetricsOverride',
    {
      width: comprobacion.viewport?.[0] ?? 1280,
      height: comprobacion.viewport?.[1] ?? 800,
      deviceScaleFactor: 1,
      mobile: false,
    },
    sessionId
  );
  await navegar(url(comprobacion.url));
  if (comprobacion.preparacion) await ejecutar(comprobacion.preparacion);
  for (const _ of comprobacion.teclas ?? []) await pulsarTab();
  if (comprobacion.espera) await dormir(comprobacion.espera);
  const valor = await evaluar(comprobacion.expresion);
  const cumple = comprobacion.coincide(valor);
  resultados.push({ ...comprobacion, valor, cumple });
  console.error(`${cumple ? 'ok  ' : 'FALLA'} ${comprobacion.id} ${comprobacion.nombre}`);
}

const fallan = resultados.filter((r) => !r.cumple);
const lineas = [
  '| # | Bloque | Comprobación | Criterio | Resultado | Valor observado |',
  '|---|---|---|---|---|---|',
  ...resultados.map(
    (r) =>
      `| ${r.id} | ${r.bloque} | ${r.nombre} | ${r.criterio} | ${r.cumple ? 'Cumple' : '**No cumple**'} | \`${String(r.valor)}\` |`
  ),
  '',
  `Ejecutadas ${resultados.length} comprobaciones sobre \`${rutaPrototipo}\` en Chrome sin interfaz: ${resultados.length - fallan.length} cumplen y ${fallan.length} no cumplen.`,
];
console.log(lineas.join('\n'));

ws.close();
proceso.kill();
await dormir(500);
try {
  rmSync(perfil, { recursive: true, force: true });
} catch {
  /* Chrome todavía mantiene algún archivo del perfil; el sistema lo limpia después */
}
process.exit(fallan.length > 0 ? 1 : 0);
