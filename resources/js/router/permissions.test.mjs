import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'permissions.js'), 'utf8');
const { can, canAny, hasPermissions } = await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

const user = { permissions: ['rbac.asignar', 'prescripciones.crear'] };

assert.equal(can(user, 'rbac.asignar'), true);
assert.equal(can(user, 'rbac.eliminar'), false);
assert.equal(can(null, 'rbac.asignar'), false);
assert.equal(can({}, 'rbac.asignar'), false);
assert.equal(canAny(user, ['x', 'prescripciones.crear']), true);
assert.equal(canAny(user, ['x', 'y']), false);
assert.equal(canAny(user, 'no-array'), false);

// Regresion: si `permissions` no es un array, se deniega (falla segura).
assert.equal(can({ permissions: 'rbac.asignar' }, 'rbac.asignar'), false);
assert.equal(canAny({ permissions: 'x' }, ['x']), false);

// Regresion: payload real de login/me (roles y tenant, SIN permissions).
const loginUser = { id: 1, tenant_id: 2, roles: ['Recepcionista'], tenant: { id: 2 } };
assert.equal(hasPermissions(loginUser), false);
assert.equal(can(loginUser, 'rbac.asignar'), false);

// hasPermissions distingue la fuente verificada para no redirigir sin datos.
assert.equal(hasPermissions(user), true);
assert.equal(hasPermissions(null), false);
assert.equal(hasPermissions({}), false);

console.log('OK: pruebas de permisos del router pasaron.');
