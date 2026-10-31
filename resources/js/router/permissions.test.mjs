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
// `/` es publica y sin permiso: aparece solo cuando el usuario tiene sesion
// con permisos publicados (cualquier otro item sin ruta dice «proximamente»).
assert.deepEqual(visibleNavigation(sessionUser).map((entry) => entry.to), ['/', '/ocupacion', '/patients']);
assert.deepEqual(visibleNavigation(null), []);
assert.deepEqual(visibleNavigation(sessionUser, []), []);

// Regresion: entradas quedan sujetas al backend; los items «proximamente»
// se muestran solo si el usuario tiene el permiso del modulo correspondiente.
assert.equal(visibleNavigation(sessionUser).some((entry) => entry.to === null), false);
const grantedSoon = visibleNavigation({ permissions: ['citas.ver'] }, NAVIGATION).filter((entry) => entry.to === null);
assert.deepEqual(grantedSoon.map((entry) => entry.label), ['Citas']);
assert.equal(visibleNavigation(null).filter((entry) => entry.to === null).length, 0);

// Regresion ASII-08: /traslados existe como ruta, asi que el menu debe ofrecerla a quien tenga traslados.ver,
// y solo a quien lo tenga (el permiso sigue en rbac.pending hasta que RBAC lo active).
const conTraslados = visibleNavigation({ permissions: ['traslados.ver'] }, NAVIGATION);
assert.deepEqual(conTraslados.filter((entry) => entry.to === '/traslados').map((entry) => entry.label), ['Traslados y altas']);
assert.equal(visibleNavigation(sessionUser).some((entry) => entry.to === '/traslados'), false);

const laboratoryUser = { permissions: ['laboratorio.ordenes.ver', 'lab-results.read'] };
assert.deepEqual(visibleNavigation(laboratoryUser).filter((entry) => entry.to?.startsWith('/lab-')).map((entry) => entry.label), ['Ingreso de resultados', 'Órdenes de laboratorio']);
assert.equal(visibleNavigation({ permissions: ['documentacion.ver'] }).find((entry) => entry.to === '/documentacion').label, 'Documentación de APIs');

// Cada entrada de navegacion declara un permiso del catalogo del backend
// o es una ruta publica sin permiso (con `to` definido).
const catalog = readFileSync(join(here, '..', '..', '..', 'config', 'rbac.php'), 'utf8');
NAVIGATION.forEach((entry) => {
    if (entry.permission === null) {
        assert.ok(entry.to, `entrada sin permiso debe declarar ruta: ${entry.label}`);
        assert.equal(entry.to, '/', 'la unica entrada sin permiso es la ruta publica inicial');
        return;
    }
    assert.ok(catalog.includes(`'${entry.permission}'`), `entrada fuera del catalogo del backend: ${entry.to}`);
});

// Las acciones de Pacientes usan el permiso de la accion, no el de lectura.
assert.equal(ROUTE_PERMISSIONS.patients, 'pacientes.ver');
assert.equal(ROUTE_PERMISSIONS['patients-new'], 'pacientes.registrar');
assert.equal(ROUTE_PERMISSIONS['patients-edit'], 'pacientes.editar');
assert.equal(ROUTE_PERMISSIONS.prescripciones, 'prescripciones.ver');
assert.equal(NAVIGATION.filter((entry) => entry.label === 'Prescripciones').length, 1);
assert.deepEqual(visibleNavigation({ permissions: ['prescripciones.ver'] }).filter((entry) => entry.to === '/prescripciones').map((entry) => entry.label), ['Prescripciones']);

// Un lector no puede entrar por URL a registrar o editar; cada accion es independiente.
for (const action of ['patients-new', 'patients-edit']) {
    const meta = { permission: ROUTE_PERMISSIONS[action] };
    assert.equal(allowsRoute({ permissions: ['pacientes.ver'] }, meta), false);
    assert.equal(allowsRoute({ permissions: [meta.permission] }, meta), true);
    assert.equal(allowsRoute(null, meta), false);
}
assert.equal(allowsRoute({ permissions: ['pacientes.registrar'] }, { permission: ROUTE_PERMISSIONS['patients-edit'] }), false);
assert.equal(allowsRoute({ permissions: ['pacientes.editar'] }, { permission: ROUTE_PERMISSIONS['patients-new'] }), false);

// Todo permiso de la interfaz existe en el catalogo del backend (config/rbac.php);
// ya se leyo para la navegacion, aqui se reutiliza.
Object.values(ROUTE_PERMISSIONS).forEach((permission) => {
    assert.ok(catalog.includes(`'${permission}'`), `permiso fuera del catalogo del backend: ${permission}`);
});

// Toda ruta protegida del enrutador declara el permiso que exige el backend.
const routerSource = readFileSync(join(here, 'index.js'), 'utf8');
assert.match(routerSource, /path: '\/prescripciones'[\s\S]*?permission: ROUTE_PERMISSIONS\.prescripciones/);
const protectedRoutes = routerSource.match(/requiresAuth: true[^\n]*/g) ?? [];
assert.ok(protectedRoutes.length >= 6, 'el enrutador debe seguir declarando rutas protegidas');
protectedRoutes.forEach((route) => {
    assert.match(route, /[{,]\s*permission\b/, `ruta protegida sin permiso declarado: ${route.trim()}`);
});

console.log('OK: pruebas de permisos del router pasaron.');
