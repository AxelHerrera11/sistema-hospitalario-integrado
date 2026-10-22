import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';
import {
    allowsRoute,
    can,
    canAny,
    hasPermissions,
    NAVIGATION,
    requiredPermissions,
    ROUTE_PERMISSIONS,
    visibleNavigation,
} from './permissions.js';

const here = dirname(fileURLToPath(import.meta.url));

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

// La entrega de Cindy publica `user.permissions` en login, /auth/me y renovacion.
const sessionUser = { user_id: 'u-1', email: 'a@b.test', permissions: ['ocupacion.ver', 'pacientes.ver'] };
assert.equal(hasPermissions(sessionUser), true);
assert.equal(can(sessionUser, 'ocupacion.ver'), true);
assert.equal(can(sessionUser, 'rbac.asignar'), false);

// Sin `permissions` se deniega: payload antiguo o sesion no renovada.
const legacyUser = { id: 1, tenant_id: 2, roles: ['Recepcionista'], tenant: { id: 2 } };
assert.equal(hasPermissions(legacyUser), false);
assert.equal(can(legacyUser, 'rbac.asignar'), false);

// hasPermissions distingue la fuente verificada para no redirigir sin datos.
assert.equal(hasPermissions(user), true);
assert.equal(hasPermissions(null), false);
assert.equal(hasPermissions({}), false);

// Permisos exigidos por una ruta.
assert.deepEqual(requiredPermissions({ permission: 'ocupacion.ver' }), ['ocupacion.ver']);
assert.deepEqual(requiredPermissions({ permissions: ['a', 'b', 'a'] }), ['a', 'b']);
assert.deepEqual(requiredPermissions({ permission: 'x', permissions: ['y'] }), ['x', 'y']);
assert.deepEqual(requiredPermissions({}), []);
assert.deepEqual(requiredPermissions(null), []);
assert.deepEqual(requiredPermissions({ permission: '' }), []);

// Una ruta protegida exige permiso; sin permisos publicados se deniega.
assert.equal(allowsRoute(sessionUser, { permission: 'ocupacion.ver' }), true);
assert.equal(allowsRoute(sessionUser, { permission: 'usuarios.ver' }), false);
assert.equal(allowsRoute(legacyUser, { permission: 'ocupacion.ver' }), false);
assert.equal(allowsRoute(null, { permission: 'ocupacion.ver' }), false);
assert.equal(allowsRoute(sessionUser, {}), true);

// La navegacion solo ofrece lo que el usuario puede ver.
assert.deepEqual(visibleNavigation(sessionUser).map((entry) => entry.to), ['/ocupacion', '/patients']);
assert.deepEqual(visibleNavigation(legacyUser), []);
assert.deepEqual(visibleNavigation(null), []);
assert.deepEqual(visibleNavigation(sessionUser, []), []);

// Cada entrada de navegacion declara un permiso del catalogo de rutas.
NAVIGATION.forEach((entry) => {
    assert.ok(Object.values(ROUTE_PERMISSIONS).includes(entry.permission), `entrada sin permiso conocido: ${entry.to}`);
});

// Las acciones de Pacientes usan el permiso de la accion, no el de lectura.
assert.equal(ROUTE_PERMISSIONS.patients, 'pacientes.ver');
assert.equal(ROUTE_PERMISSIONS['patients-new'], 'pacientes.registrar');
assert.equal(ROUTE_PERMISSIONS['patients-edit'], 'pacientes.editar');

// Un lector no puede entrar por URL a registrar o editar; cada accion es independiente.
for (const action of ['patients-new', 'patients-edit']) {
    const meta = { permission: ROUTE_PERMISSIONS[action] };
    assert.equal(allowsRoute({ permissions: ['pacientes.ver'] }, meta), false);
    assert.equal(allowsRoute({ permissions: [meta.permission] }, meta), true);
    assert.equal(allowsRoute(null, meta), false);
}
assert.equal(allowsRoute({ permissions: ['pacientes.registrar'] }, { permission: ROUTE_PERMISSIONS['patients-edit'] }), false);
assert.equal(allowsRoute({ permissions: ['pacientes.editar'] }, { permission: ROUTE_PERMISSIONS['patients-new'] }), false);

// Todo permiso de la interfaz existe en el catalogo del backend (config/rbac.php).
const catalog = readFileSync(join(here, '..', '..', '..', 'config', 'rbac.php'), 'utf8');
Object.values(ROUTE_PERMISSIONS).forEach((permission) => {
    assert.ok(catalog.includes(`'${permission}'`), `permiso fuera del catalogo del backend: ${permission}`);
});

// Toda ruta protegida del enrutador declara el permiso que exige el backend.
const routerSource = readFileSync(join(here, 'index.js'), 'utf8');
const protectedRoutes = routerSource.match(/requiresAuth: true[^\n]*/g) ?? [];
assert.ok(protectedRoutes.length >= 6, 'el enrutador debe seguir declarando rutas protegidas');
protectedRoutes.forEach((route) => {
    assert.match(route, /[{,]\s*permission\b/, `ruta protegida sin permiso declarado: ${route.trim()}`);
});

console.log('OK: pruebas de permisos del router pasaron.');
