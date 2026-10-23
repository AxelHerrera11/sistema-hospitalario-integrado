import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import assert from 'node:assert/strict';

const here = dirname(fileURLToPath(import.meta.url));
const source = readFileSync(join(here, 'formRules.js'), 'utf8');
const { passwordStrength, validateLogin, createSubmitGuard, PASSWORD_CHECKS } =
    await import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'));

// Fuerza de contraseña alineada con PasswordPolicy del backend.
assert.equal(passwordStrength('').puntaje, 0);
assert.equal(passwordStrength('').nivel, 'MUY DÉBIL');
assert.equal(passwordStrength('abc').nivel, 'MUY DÉBIL');
assert.equal(passwordStrength('abcDEF').nivel, 'DÉBIL');
assert.equal(passwordStrength('Clave-Segura-2026!').valida, true);
assert.equal(passwordStrength('Clave-Segura-2026!').porcentaje, 100);
assert.equal(passwordStrength('ClaveSegura2026').valida, false, 'sin símbolo no es válida');
assert.equal(passwordStrength(undefined).puntaje, 0);
assert.equal(passwordStrength('x').checks.length, PASSWORD_CHECKS.length);

// Validación del login.
const tenant = '26adadaf-4d97-41d7-ae84-4f1f4c8e83a9';
assert.deepEqual(validateLogin({ tenantId: tenant, email: 'ana@hospital.test', password: 'x' }), {});
assert.equal(validateLogin({ tenantId: '', email: '', password: '' }).tenantId, 'Indique el ID del hospital');
assert.equal(validateLogin({ tenantId: 'hospital-1', email: 'a@b.co', password: 'x' }).tenantId, 'El ID del hospital debe ser un UUID');
assert.equal(validateLogin({ tenantId: tenant, email: 'sin-arroba', password: 'x' }).email, 'Formato de correo inválido');
// No se exige un dominio institucional fijo: cada hospital usa el suyo.
assert.deepEqual(validateLogin({ tenantId: tenant, email: 'dra@otro-hospital.org', password: 'x' }), {});

// Regresión: un doble clic o un Enter repetido durante loading no envía dos veces.
const guard = createSubmitGuard();
let envios = 0;
let liberar;
const lenta = () => new Promise((resolve) => {
    envios += 1;
    liberar = resolve;
});
const primera = guard.run(lenta);
const segunda = guard.run(lenta);
assert.equal(guard.pending, true);
assert.equal(primera, segunda, 'la segunda llamada reutiliza la promesa en curso');
await Promise.resolve();
assert.equal(envios, 1);
liberar();
await primera;
assert.equal(guard.pending, false);
await guard.run(async () => { envios += 1; });
assert.equal(envios, 2, 'tras terminar se puede volver a enviar');

// Un error libera el guard para reintentar.
await assert.rejects(guard.run(async () => { throw new Error('red'); }));
assert.equal(guard.pending, false);

console.log('OK: reglas de formularios responsive de Tenants aprobadas');
