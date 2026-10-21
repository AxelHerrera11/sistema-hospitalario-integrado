#!/usr/bin/env node
/*
 * Comprobaciones ejecutables del aislamiento por hospital del prototipo RBAC (Semana 11).
 *
 * Ejercitan las funciones REALES de `app.js`: lo cargan en un contexto Node (vm) con un
 * DOM simulado mínimo y exponen sus bindings internos. No reimplementan la lógica productiva.
 *
 * Uso:
 *   node pruebas-aislamiento.mjs [ruta/app.js]
 * Por defecto lee ./app.js. Después de integrar #149 en `develop`:
 *   node docs/rbac/semana-11/pruebas-aislamiento.mjs docs/rbac/semana-11/app.js
 * Antes de esa integración, ejecutar sobre un worktree temporal que combine la lógica
 * corregida de #149 con este archivo de #151 (no duplicar app.js en #151).
 *
 * Demostración con datos simulados: no es seguridad real de producción.
 */
import { readFileSync } from 'node:fs';
import { createContext, runInContext } from 'node:vm';
import { resolve } from 'node:path';

const appPath = resolve(process.argv[2] || process.env.RBAC_APP || 'app.js');
const source = readFileSync(appPath, 'utf8');

/* ---------- DOM simulado mínimo (solo lo que app.js toca) ---------- */
const els = {};
function makeEl() {
    const el = {
        textContent: '', className: '', hidden: false, disabled: false, value: '',
        children: [], dataset: {},
        addEventListener() {}, setAttribute() {}, removeAttribute() {}, focus() {},
        appendChild(child) { el.children.push(child); },
    };
    let html = '';
    Object.defineProperty(el, 'innerHTML', { get: () => html, set: (v) => { html = v; el.children = []; } });
    return el;
}
const documentStub = {
    getElementById(id) { return els[id] || (els[id] = makeEl()); },
    querySelectorAll() { return []; },
    querySelector() { return null; },
    createElement() { return makeEl(); },
    addEventListener() {},
};
const locationStub = { hash: '', search: '' };
const windowStub = {
    addEventListener() {},
    setTimeout(fn) { return fn(); },
    crypto: { getRandomValues(arr) { for (let i = 0; i < arr.length; i++) { arr[i] = (i * 7 + 1) & 255; } return arr; } },
};
const context = createContext({
    document: documentStub, window: windowStub, location: locationStub,
    navigator: {}, console, setTimeout: windowStub.setTimeout, URLSearchParams,
});

const exportLine = '\n;globalThis.__rbac = { USERS, ROLES, ROLE_PERMS, PERMISSIONS, state, usersOf, hasPerm,'
    + ' confirmAssignment, renderSession, renderRoles, renderAudit, seedDemoAudit, recordDenial };\n';
runInContext(source + exportLine, context);
const api = context.__rbac;

/* ---------- Utilidades de prueba ---------- */
const original = JSON.parse(JSON.stringify(api.USERS));
function reset() {
    Object.assign(api.USERS, JSON.parse(JSON.stringify(original)));
    api.state.actor = 'admin1'; api.state.hospital = 'H1';
    api.state.pending = null; api.state.decision = null;
}
const options = (id) => els[id].children.map((c) => c.value);
let passed = 0; let failed = 0;
function check(name, cond) {
    if (cond) { passed += 1; console.log('OK    ' + name); } else { failed += 1; console.log('FALLO ' + name); }
}

/* 1. Sesión H1: usuarios visibles solo de H1 */
reset();
api.state.actor = 'admin1'; api.renderSession(); api.renderRoles();
const h1 = api.usersOf('H1'); const h1Opts = options('targetUser');
check('1. Sesión H1 ve solo usuarios de H1',
    h1.length > 0 && h1.every((k) => api.USERS[k].hospital === 'H1')
    && h1Opts.every((k) => api.USERS[k].hospital === 'H1') && !h1Opts.includes('admin2'));

/* 2. Sesión H2: contexto y usuarios visibles solo de H2 */
api.state.actor = 'admin2'; api.renderSession(); api.renderRoles();
const h2Opts = options('targetUser');
check('2. Sesión H2 fija hospital H2 y ve solo usuarios de H2',
    api.state.hospital === 'H2' && h2Opts.length > 0
    && h2Opts.every((k) => api.USERS[k].hospital === 'H2') && h2Opts.includes('nuevo2'));

/* 3. Asignación válida dentro del hospital autorizado */
reset();
api.state.actor = 'admin1'; api.state.hospital = 'H1'; api.state.pending = { target: 'nuevo1', newRole: 'enfermeria' };
api.confirmAssignment();
check('3. Asignación válida dentro del hospital aplica el rol', api.USERS.nuevo1.role === 'enfermeria');

/* 4. Rechazo de asignación entre hospitales (invocando confirmAssignment directamente) */
reset();
api.state.actor = 'admin1'; api.state.hospital = 'H1'; api.state.pending = { target: 'nuevo2', newRole: 'enfermeria' };
api.confirmAssignment();
check('4. Asignación H1 hacia usuario de H2 es rechazada',
    !!api.state.decision && api.state.decision.reason === 'usuario_de_otro_hospital');

/* 5. El rechazo no modifica el rol */
check('5. El rol del usuario de H2 no cambia tras el rechazo', api.USERS.nuevo2.role === null);

/* 6. Auditoría aislada por hospital */
reset();
api.state.actor = 'admin1'; api.state.hospital = 'H1'; api.renderAudit();
const h1Log = els.auditWrap.innerHTML;
api.state.actor = 'admin2'; api.state.hospital = 'H2'; api.renderAudit();
const h2Log = els.auditWrap.innerHTML;
check('6. Bitácora aislada (H1 no ve H2 y H2 no ve H1)',
    h1Log.includes('Enf. Demo') && !h1Log.includes('Usuario nuevo H2')
    && h2Log.includes('Usuario nuevo H2') && !h2Log.includes('Enf. Demo'));

/* 7. Permisos y anti-escalamiento se conservan */
reset();
api.state.actor = 'med1'; api.state.hospital = 'H1'; api.state.pending = { target: 'nuevo1', newRole: 'enfermeria' };
api.confirmAssignment();
const sinPermiso = !!api.state.decision && api.state.decision.reason === 'sin_permiso_de_rol' && api.USERS.nuevo1.role === null;
reset();
api.state.actor = 'admin1'; api.state.hospital = 'H1'; api.state.pending = { target: 'nuevo1', newRole: 'superadmin' };
api.confirmAssignment();
const sinEscalar = !!api.state.decision && api.state.decision.reason === 'rol_supera_privilegio' && api.USERS.nuevo1.role === null;
check('7. Permisos y anti-escalamiento se conservan', sinPermiso && sinEscalar);

console.log(`\n[aislamiento por hospital] ${passed + failed} comprobaciones, ${failed} fallos.`);
process.exit(failed === 0 ? 0 : 1);
