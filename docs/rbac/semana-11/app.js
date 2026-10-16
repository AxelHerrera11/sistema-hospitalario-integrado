'use strict';

/*
 * Modelo de permisos adoptado para Semana 11: rbac.roles.asignar protege membresías;
 * rbac.permisos.asignar protege la asignación/actualización de permisos de un rol.
 * La implementación de Semana 3 conserva rbac.asignar y no se modifica desde esta rama.
 */
const ROLES = {
    superadmin: { label: 'SuperAdmin', level: 100 },
    admin_hospital: { label: 'AdminHospital', level: 80 },
    medico: { label: 'Medico', level: 50 },
    enfermeria: { label: 'Enfermeria', level: 40 },
    recepcion: { label: 'Recepcionista', level: 20 },
};

const PERMISSIONS = {
    'rbac.roles.ver': 'Ver roles y sus permisos',
    'rbac.roles.asignar': 'Asignar un rol a un usuario',
    'rbac.permisos.asignar': 'Asignar o actualizar permisos de un rol',
    'rbac.autorizar': 'Solicitar una decisión de autorización',
    'rbac.permisos.ver': 'Ver el catálogo de permisos',
    'rbac.denegaciones.ver': 'Ver el historial de denegaciones',
    'prescripciones.ver': 'Ver prescripciones',
    'prescripciones.crear': 'Crear prescripciones',
    'prescripciones.firmar': 'Firmar prescripciones',
    'prescripciones.excepcion_alergia': 'Registrar excepción de alergia',
    'muestras.recibir': 'Recibir muestras',
    'muestras.ver': 'Ver muestras',
    'muestras.etiquetar': 'Etiquetar muestras',
};

const ROLE_PERMS = {
    superadmin: Object.keys(PERMISSIONS),
    admin_hospital: ['rbac.roles.ver', 'rbac.roles.asignar', 'rbac.permisos.asignar', 'rbac.permisos.ver', 'rbac.denegaciones.ver'],
    medico: ['rbac.autorizar', 'prescripciones.ver', 'prescripciones.crear', 'prescripciones.firmar', 'prescripciones.excepcion_alergia'],
    enfermeria: ['rbac.autorizar', 'prescripciones.ver', 'muestras.recibir', 'muestras.ver', 'muestras.etiquetar'],
    recepcion: ['rbac.autorizar', 'muestras.recibir', 'muestras.ver'],
};

/* Usuarios ficticios (sin datos personales ni clínicos reales). */
const USERS = {
    admin1: { name: 'Admin de hospital H1', role: 'admin_hospital', hospital: 'H1' },
    med1: { name: 'Dra. Demo (Medico)', role: 'medico', hospital: 'H1' },
    enf1: { name: 'Enf. Demo', role: 'enfermeria', hospital: 'H1' },
    recep1: { name: 'Recepcionista Demo', role: 'recepcion', hospital: 'H1' },
    nuevo1: { name: 'Usuario nuevo (sin rol)', role: null, hospital: 'H1' },
};

const RESOURCES = {
    'crear-prescripcion': { label: 'Crear prescripción', path: 'POST /api/v1/prescripciones', perm: 'prescripciones.crear' },
    'etiquetar-muestra': { label: 'Etiquetar muestra', path: 'POST /api/v1/muestras/{id}/etiquetas', perm: 'muestras.etiquetar' },
    'asignar-rol': { label: 'Asignar rol', path: 'POST /api/v1/rbac/users/{id}/roles', perm: 'rbac.roles.asignar' },
    'asignar-permisos-rol': { label: 'Actualizar permisos de un rol', path: 'POST /api/v1/roles/{role}/permissions', perm: 'rbac.permisos.asignar' },
    'ver-denegaciones': { label: 'Ver denegaciones', path: 'GET /api/v1/denials', perm: 'rbac.denegaciones.ver' },
};

const state = {
    actor: 'admin1',
    target: 'nuevo1',
    newRole: 'enfermeria',
    resource: 'etiquetar-muestra',
    pending: null,
    decision: null,
    clock: new Date('2026-10-15T22:00:00'),
    audit: [],
};

const $ = (id) => document.getElementById(id);
const $$ = (sel) => Array.from(document.querySelectorAll(sel));

function roleLabel(key) { return key ? ROLES[key].label : 'Sin rol'; }
function permsOf(roleKey) { return roleKey ? ROLE_PERMS[roleKey] : []; }
function hasPerm(userKey, perm) { return permsOf(USERS[userKey].role).includes(perm); }
/* Semana 3 genera trace- + 12 dígitos hexadecimales con random_bytes(6). */
function trace() {
    const bytes = new Uint8Array(6);
    if (window.crypto && window.crypto.getRandomValues) { window.crypto.getRandomValues(bytes); }
    else { bytes.forEach((_, i) => { bytes[i] = Math.floor(Math.random() * 256); }); }
    return 'trace-' + Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
}
function stamp() {
    state.clock = new Date(state.clock.getTime() + 2 * 60000);
    const d = state.clock;
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}
function audit(event, actorKey, detail, traceId) {
    state.audit.unshift({ when: stamp(), event, actor: USERS[actorKey].name, hospital: USERS[actorKey].hospital, detail, trace: traceId || '—' });
}

function announce(msg) { $('live').textContent = ''; window.setTimeout(() => { $('live').textContent = msg; }, 30); }

function fillSelect(el, entries, selected) {
    el.innerHTML = '';
    entries.forEach(([value, text]) => {
        const opt = document.createElement('option');
        opt.value = value;
        opt.textContent = text;
        if (value === selected) { opt.selected = true; }
        el.appendChild(opt);
    });
}

function permsHtml(roleKey) {
    if (!roleKey) { return '<li>Sin permisos (usuario sin rol asignado).</li>'; }
    return permsOf(roleKey).map((p) => `<li><code>${p}</code> — ${PERMISSIONS[p]}</li>`).join('');
}

/* ---------- Rendering ---------- */
function renderSession() {
    fillSelect($('sessionUser'), Object.keys(USERS).map((k) => [k, USERS[k].name]), state.actor);
    const u = USERS[state.actor];
    $('sessionInfo').textContent = `Rol: ${roleLabel(u.role)} · Hospital: ${u.hospital}`;
    $$('[data-actor]').forEach((el) => { el.textContent = `Opera: ${u.name} — Rol: ${roleLabel(u.role)} — Hospital: ${u.hospital} (sesión simulada)`; });
}

function renderRoles() {
    state.pending = null;
    state.decision = null;
    fillSelect($('targetUser'), Object.keys(USERS).map((k) => [k, USERS[k].name]), state.target);
    fillSelect($('newRole'), Object.keys(ROLES).map((k) => [k, ROLES[k].label]), state.newRole);
    const t = USERS[state.target];
    $('currentRole').textContent = roleLabel(t.role);
    $('currentPerms').innerHTML = permsHtml(t.role);
    $('newPerms').innerHTML = permsHtml(state.newRole);

    const canAssign = hasPerm(state.actor, 'rbac.roles.asignar');
    const same = t.role === state.newRole;
    const missing = [];
    if (!canAssign) { missing.push('falta el permiso rbac.roles.asignar en la sesión'); }
    if (same) { missing.push('el usuario ya tiene ese rol'); }
    $('btnAssign').disabled = missing.length > 0;
    $('reqHint').textContent = missing.length ? 'No se puede asignar: ' + missing.join('; ') + '.' : '';
    $('rolesMsg').textContent = '';
    $('rolesMsg').className = 'result';
}

function renderConfirmar() {
    const p = state.pending || { target: state.target, newRole: state.newRole };
    $('cUser').textContent = USERS[p.target].name;
    $('cFrom').textContent = roleLabel(USERS[p.target].role);
    $('cTo').textContent = roleLabel(p.newRole);
    $('cHospital').textContent = USERS[p.target].hospital;
    $('cActor').textContent = USERS[state.actor].name;
}

function renderRolesExito() {
    const p = state.pending || { target: state.target, newRole: state.newRole };
    $('okDetail').textContent = `Se asignó el rol ${roleLabel(p.newRole)} a ${USERS[p.target].name} en el hospital ${USERS[p.target].hospital}.`;
}

function renderOperacion() {
    state.decision = null;
    fillSelect($('resource'), Object.keys(RESOURCES).map((k) => [k, RESOURCES[k].label]), state.resource);
    $('resPath').textContent = RESOURCES[state.resource].path;
    $('resPerm').textContent = RESOURCES[state.resource].perm;
    $('opMsg').textContent = '';
    $('opMsg').className = 'result';
}

function renderDecision(screen) {
    const d = state.decision || (screen === 'permitido' ? defaultAllowedDecision() : defaultDecision());
    if (d.allowed) {
        $('pUser').textContent = USERS[d.actor].name;
        $('pRole').textContent = roleLabel(USERS[d.actor].role);
        $('pPath').textContent = RESOURCES[d.resource].path;
        $('pPerm').textContent = RESOURCES[d.resource].perm;
    } else {
        const perm = d.permission || RESOURCES[d.resource].perm;
        $('dPath').textContent = RESOURCES[d.resource].path;
        $('dRole').textContent = roleLabel(USERS[d.actor].role);
        $('dPerm').textContent = perm;
        $('dReason').textContent = d.reason;
        $('dTrace').textContent = d.trace;
        $('dAudit').textContent = `${d.when} · acceso_denegado · ${USERS[d.actor].name} · ${USERS[d.actor].hospital} · ${perm}:${d.reason} · ${d.trace}`;
        const canReadDenials = hasPerm(d.actor, 'rbac.denegaciones.ver');
        $('technicalDetail').hidden = !canReadDenials;
        $('auditLink').hidden = !canReadDenials;
    }
}

function defaultDecision() {
    const resource = 'etiquetar-muestra';
    const actor = 'recep1';
    return { allowed: hasPerm(actor, RESOURCES[resource].perm), actor, resource, reason: 'sin_permiso_de_rol', trace: 'trace-9f8e7d6c5b4a', when: '2026-10-15 22:00' };
}

function defaultAllowedDecision() {
    return { allowed: true, actor: 'med1', resource: 'crear-prescripcion' };
}

function recordDenial(actorKey, perm, reason) {
    const t = trace();
    const event = { when: stamp(), event: 'acceso_denegado', actor: USERS[actorKey].name, hospital: USERS[actorKey].hospital, detail: `${perm}:${reason}`, trace: t };
    state.audit.unshift(event);
    return event;
}

function seedDemoAudit() {
    state.audit.push({ when: '2026-10-15 21:58', event: 'rol_asignado', actor: USERS.admin1.name, hospital: 'H1', detail: 'Enf. Demo: Sin rol → Enfermeria', trace: '—' });
    const d = defaultDecision();
    state.audit.push({ when: '2026-10-15 22:00', event: 'acceso_denegado', actor: USERS[d.actor].name, hospital: USERS[d.actor].hospital, detail: `${RESOURCES[d.resource].perm}:${d.reason}`, trace: d.trace });
}

function renderAudit() {
    const wrap = $('auditWrap');
    if (!hasPerm(state.actor, 'rbac.denegaciones.ver')) {
        wrap.innerHTML = '<p class="muted" role="status">No tiene autorización para consultar esta bitácora.</p>';
        return;
    }
    if (state.audit.length === 0) {
        wrap.innerHTML = '<p class="muted">Aún no hay eventos en esta sesión. Ejecute una asignación o una operación protegida.</p>';
        return;
    }
    const rows = state.audit.map((e) => `
        <tr>
            <td data-label="Fecha (demo)">${e.when}</td>
            <td data-label="Evento">${e.event}</td>
            <td data-label="Actor">${e.actor}</td>
            <td data-label="Hospital">${e.hospital}</td>
            <td data-label="Detalle">${e.detail}</td>
            <td data-label="trace_id"><code>${e.trace}</code></td>
        </tr>`).join('');
    wrap.innerHTML = `<table>
        <thead><tr><th>Fecha (demo)</th><th>Evento</th><th>Actor</th><th>Hospital</th><th>Detalle</th><th>trace_id</th></tr></thead>
        <tbody>${rows}</tbody></table>`;
}

/* ---------- Navigation ---------- */
const NAV = { roles: 'roles', confirmar: 'roles', 'roles-exito': 'roles', operacion: 'operacion', permitido: 'operacion', denegado: 'operacion' };

function route() {
    const fromQuery = new URLSearchParams(location.search).get('screen');
    let screen = (location.hash || '').replace('#', '') || fromQuery || 'inicio';
    if (!document.querySelector(`.screen[data-screen="${screen}"]`)) { screen = 'inicio'; }

    if (screen === 'denegado' && !state.decision) { state.actor = 'recep1'; renderSession(); }
    if (screen === 'permitido' && !state.decision) { state.actor = 'med1'; renderSession(); }

    $$('.screen').forEach((s) => { s.hidden = s.dataset.screen !== screen; });
    $$('[data-nav]').forEach((a) => {
        const active = a.dataset.nav === (NAV[screen] || screen);
        if (active) { a.setAttribute('aria-current', 'page'); } else { a.removeAttribute('aria-current'); }
    });

    if (screen === 'roles') { renderRoles(); }
    if (screen === 'confirmar') { state.pending = state.pending || { target: state.target, newRole: state.newRole }; renderConfirmar(); }
    if (screen === 'roles-exito') { renderRolesExito(); }
    if (screen === 'operacion') { renderOperacion(); }
    if (screen === 'permitido' || screen === 'denegado') { renderDecision(screen); }
    if (screen === 'bitacora') { renderAudit(); }

    $('main').focus({ preventScroll: true });
}

/* ---------- Actions ---------- */
function confirmAssignment() {
    const p = state.pending || { target: state.target, newRole: state.newRole };
    const from = USERS[p.target].role;

    if (!hasPerm(state.actor, 'rbac.roles.asignar')) {
        const e = recordDenial(state.actor, 'rbac.roles.asignar', 'sin_permiso_de_rol');
        state.decision = { allowed: false, actor: state.actor, resource: 'asignar-rol', reason: 'sin_permiso_de_rol', trace: e.trace, when: e.when };
        announce('Asignación denegada. Falta el permiso rbac.roles.asignar.');
        location.hash = '#denegado';
        return;
    }
    if (ROLES[p.newRole].level > ROLES[USERS[state.actor].role].level) {
        const e = recordDenial(state.actor, 'rbac.roles.asignar', 'rol_supera_privilegio');
        state.decision = { allowed: false, actor: state.actor, resource: 'asignar-rol', reason: 'rol_supera_privilegio', trace: e.trace, when: e.when };
        announce('Asignación denegada por privilegio insuficiente.');
        location.hash = '#denegado';
        return;
    }
    if (from === p.newRole) {
        $('rolesMsg').textContent = 'El rol ya estaba asignado (409). No se cambió nada.';
        $('rolesMsg').className = 'result result--err';
        announce('El rol ya estaba asignado (409).');
        location.hash = '#roles';
        return;
    }

    USERS[p.target].role = p.newRole;
    audit('rol_asignado', state.actor, `${USERS[p.target].name}: ${roleLabel(from)} → ${roleLabel(p.newRole)}`);
    state.pending = p;
    announce('Asignación exitosa (201).');
    location.hash = '#roles-exito';
}

function authorize() {
    const resource = state.resource;
    const perm = RESOURCES[resource].perm;
    if (!hasPerm(state.actor, 'rbac.autorizar')) {
        const e = recordDenial(state.actor, 'rbac.autorizar', 'sin_permiso_de_rol');
        state.decision = { allowed: false, actor: state.actor, resource, permission: 'rbac.autorizar', reason: 'sin_permiso_de_rol', trace: e.trace, when: e.when };
        announce('Acceso denegado. No tiene autorización para esta operación.');
        location.hash = '#denegado';
        return;
    }
    if (hasPerm(state.actor, perm)) {
        state.decision = { allowed: true, actor: state.actor, resource };
        announce('Autorización permitida.');
        location.hash = '#permitido';
        return;
    }
    const e = recordDenial(state.actor, perm, 'sin_permiso_de_rol');
    state.decision = { allowed: false, actor: state.actor, resource, reason: 'sin_permiso_de_rol', trace: e.trace, when: e.when };
    announce('Acceso denegado. Falta el permiso requerido.');
    location.hash = '#denegado';
}

async function copyTrace() {
    const t = $('dTrace').textContent;
    try {
        await navigator.clipboard.writeText(t);
        announce('trace_id copiado.');
    } catch {
        announce('Copia no disponible en este navegador; seleccione el texto manualmente.');
    }
}

/* ---------- Wiring ---------- */
window.addEventListener('hashchange', route);
$('sessionUser').addEventListener('change', (e) => {
    const current = (location.hash || '').slice(1);
    state.actor = e.target.value;
    state.decision = null;
    renderSession();
    if (['denegado', 'permitido', 'roles-exito'].includes(current)) {
        state.pending = null;
        location.hash = '#inicio';
        return;
    }
    route();
});
$('targetUser').addEventListener('change', (e) => { state.target = e.target.value; renderRoles(); });
$('newRole').addEventListener('change', (e) => { state.newRole = e.target.value; renderRoles(); });
$('resource').addEventListener('change', (e) => { state.resource = e.target.value; renderOperacion(); });
$('btnAssign').addEventListener('click', () => { state.pending = { target: state.target, newRole: state.newRole }; location.hash = '#confirmar'; });
$('btnConfirm').addEventListener('click', confirmAssignment);
$('btnAuthorize').addEventListener('click', authorize);
$('btnCopy').addEventListener('click', copyTrace);

renderSession();
seedDemoAudit();
route();
