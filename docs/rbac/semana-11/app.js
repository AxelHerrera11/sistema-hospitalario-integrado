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
