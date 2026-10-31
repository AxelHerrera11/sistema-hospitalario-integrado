import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'hospitalUsers.js'), 'utf8');
const {
    accionesPara, confirmacion, erroresDeAlta, estadoDe, etiquetaRol, iniciales, mensajeDeError, resumen, ROLES, rolesAsignables, rolesParaAlta,
} =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

const admin = 'a1';
const todos = { asignar: true, desactivar: true };
const activo = { user_id: 'u1', display_name: 'Ana Gómez', status: 'active', roles: ['medico'] };

// Los 8 roles canónicos, sin duplicados.
assert.equal(ROLES.length, 8);
assert.equal(new Set(ROLES.map((r) => r.valor)).size, 8);

// Solo se ofrecen roles que el usuario no tiene.
assert.ok(! rolesAsignables(activo).some((r) => r.valor === 'medico'));
assert.equal(rolesAsignables(activo).length, 7);
assert.equal(rolesAsignables({ roles: undefined }).length, 8);

// Acciones según permiso, autoedición y estado.
assert.deepEqual(accionesPara(activo, admin, todos), { asignar: true, retirar: true, desactivar: true });
assert.deepEqual(accionesPara({ ...activo, user_id: admin }, admin, todos), { asignar: false, retirar: false, desactivar: false });
assert.deepEqual(accionesPara({ ...activo, status: 'inactive' }, admin, todos), { asignar: false, retirar: true, desactivar: true });
assert.deepEqual(accionesPara(activo, admin, { asignar: false, desactivar: true }), { asignar: false, retirar: false, desactivar: true });
assert.deepEqual(accionesPara(activo, admin, { asignar: true, desactivar: false }), { asignar: true, retirar: true, desactivar: false });

// Mensajes por código; el detalle del servidor nunca se muestra.
assert.equal(mensajeDeError({ error: 'SELF_ROLE_CHANGE' }, 403), 'No puede modificar sus propios roles ni su acceso.');
assert.equal(mensajeDeError({ error: 'SUPERADMIN_REQUIRES_SUPERADMIN' }, 403), 'Solo un superadministrador puede asignar o retirar el rol superadmin.');
assert.equal(mensajeDeError({ message: 'SQLSTATE[23505]' }, 500), 'No se pudo completar la operación.');
assert.equal(mensajeDeError({ error: 'RBAC_DENIED' }, 403), 'No tiene permiso para esta acción.');
assert.equal(mensajeDeError(undefined, 401), 'Inicie sesión para continuar.');

// Confirmaciones legibles.
assert.equal(etiquetaRol('tecnico_laboratorio'), 'Técnico de laboratorio');
assert.equal(etiquetaRol('otro'), 'otro');
assert.equal(confirmacion('retirar', activo, 'medico'), '¿Retirar el rol «Medicina» a Ana Gómez?');
assert.match(confirmacion('desactivar', { email: 'x@demo.test' }), /x@demo\.test/);


// Presentación: iniciales, estado en español y resumen de la página.
assert.equal(iniciales(activo), 'AG');
assert.equal(iniciales({ email: 'luis.perez@demo.test' }), 'LP');
assert.equal(iniciales({}), '?');
assert.deepEqual(estadoDe('active'), { etiqueta: 'Activo', tono: 'ok' });
assert.equal(estadoDe('inactive').etiqueta, 'Inactivo');
assert.deepEqual(estadoDe('raro'), { etiqueta: 'raro', tono: 'off' });
assert.deepEqual(resumen([activo, { status: 'inactive', roles: [] }], 40), { total: 40, activos: 1, sinRoles: 1 });
assert.deepEqual(resumen(undefined), { total: 0, activos: 0, sinRoles: 0 });

// Alta de usuario: superadmin solo para superadmin y validación previa con la política del backend.
assert.equal(rolesParaAlta(['admin_hospital']).length, 7);
assert.ok(! rolesParaAlta(['admin_hospital']).some((r) => r.valor === 'superadmin'));
assert.equal(rolesParaAlta(['superadmin']).length, 8);
assert.equal(rolesParaAlta(undefined).length, 7);
const alta = { display_name: 'Dra. Nueva', email: 'nueva@demo.test', role_code: 'medico', password: 'Temporal-2026!x' };
assert.deepEqual(erroresDeAlta(alta), {});
assert.deepEqual(Object.keys(erroresDeAlta({})).sort(), ['display_name', 'email', 'password', 'role_code']);
for (const clave of ['Corta-1!', 'sin-mayusculas-2026!', 'SinNumerosNiNada!', 'SinSimbolos2026x']) {
    assert.ok(erroresDeAlta({ ...alta, password: clave }).password, clave);
}
assert.ok(erroresDeAlta({ ...alta, email: 'sin-arroba' }).email);
assert.equal(mensajeDeError({ error: 'EMAIL_TAKEN' }, 409), 'Ya existe una cuenta con ese correo.');
assert.match(mensajeDeError({ errors: { password: ['x'] } }, 422), /12 caracteres/);

console.log('hospitalUsers.test.mjs: OK');
