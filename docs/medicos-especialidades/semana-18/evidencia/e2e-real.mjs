#!/usr/bin/env node
/**
 * Recorrido de punta a punta de Médicos (ASII-04) contra el backend REAL: Laravel, JWT, tenant, RBAC y PostgreSQL.
 * No usa dobles: todo pasa por `/api/v1` con un token obtenido por `/auth/login`.
 *
 * Requiere un servidor de Laravel ya levantado sobre bases EFÍMERAS y con datos ficticios (ver `e2e-real.md`).
 * Variables (todas obligatorias):
 *   E2E_BASE            URL del servidor, p. ej. http://127.0.0.1:8010
 *   E2E_HOSPITAL_ID     UUID del hospital sembrado     E2E_HOSPITAL_2_ID   UUID del otro hospital sembrado
 *   E2E_ADMIN_EMAIL     usuario con rol admin_hospital  E2E_MEDICO_EMAIL    usuario con rol medico
 *   E2E_PASSWORD        contraseña de ambos             E2E_DOCTOR_REF      local_user_ref_id que será el médico
 *   E2E_PSQL            comando que ejecuta SQL en la base HOSPITAL (lee el SQL por stdin), p. ej.
 *                       "docker exec -i <contenedor> psql -U postgres -d his_hospital_e2e -At"
 *
 * Salida: tabla Markdown por stdout; código de salida 1 si algo no cumple.
 */
import { execSync } from 'node:child_process';

const need = (n) => process.env[n] ?? (console.error(`Falta la variable ${n}`), process.exit(2));
const BASE = need('E2E_BASE'), H1 = need('E2E_HOSPITAL_ID'), H2 = need('E2E_HOSPITAL_2_ID');
const PASS = need('E2E_PASSWORD'), REF = need('E2E_DOCTOR_REF'), PSQL = need('E2E_PSQL');
const sql = (text) => execSync(PSQL, { input: text, encoding: 'utf8' }).trim();

async function call(method, path, { token, tenant = H1, body, headers = {} } = {}) {
    const res = await fetch(`${BASE}/api/v1${path}`, {
        method,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(tenant ? { 'X-Tenant-ID': tenant } : {}), ...(token ? { Authorization: `Bearer ${token}` } : {}), ...headers },
        body: body ? JSON.stringify(body) : undefined,
    });
    const text = await res.text();
    let json = null; try { json = JSON.parse(text); } catch { /* respuesta no JSON */ }
    return { status: res.status, json, text, headers: res.headers };
}
const login = async (email) => (await call('POST', '/auth/login', { body: { email, password: PASS } })).json?.access_token;

const rows = []; let failed = 0;
const check = (name, ok, detail) => { rows.push(`| ${rows.length + 1} | ${name} | ${ok ? 'Cumple' : '**No cumple**'} | ${detail} |`); ok || failed++; };
const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;

const admin = await login(process.env.E2E_ADMIN_EMAIL), medico = await login(process.env.E2E_MEDICO_EMAIL);
check('Login real de admin_hospital y de medico (JWT)', Boolean(admin && medico), 'dos tokens emitidos por /auth/login');

const alta = { local_user_ref_id: REF, license_number: ' col-e2e-1 ', specialty_code: 'CARD', valid_from: '2026-01-01' };
const created = await call('POST', '/doctors', { token: admin, body: alta });
const id = created.json?.doctor_profile_id;
check('Alta como admin_hospital: 201, id uuid y trace_id', created.status === 201 && UUID.test(id) && /^[0-9a-f]{16}$/.test(created.json?.trace_id ?? '') && created.headers.get('x-trace-id') === created.json.trace_id, `${created.status} · ${id}`);
check('Respuesta sin caché compartida', created.headers.get('cache-control') === 'no-store, private', created.headers.get('cache-control') ?? '(ausente)');
check('Licencia guardada normalizada (D-01)', sql(`SELECT license_number FROM doctor_profiles WHERE doctor_profile_id = '${id}'`) === 'COL-E2E-1', sql(`SELECT license_number FROM doctor_profiles WHERE doctor_profile_id = '${id}'`));
const audit = sql(`SELECT module || '|' || action || '|' || resource_type FROM local_access_audits WHERE resource_id = '${id}'`);
check('Auditoría H8 con el actor local, no con el id del JWT', audit === 'medicos|medicos.medico_registrado|doctor_profile' && sql(`SELECT count(*) FROM local_access_audits a JOIN local_user_refs u ON u.local_user_ref_id = a.actor_user_ref_id WHERE a.resource_id = '${id}' AND u.role_code = 'admin_hospital'`) === '1', audit);

const dup = await call('POST', '/doctors', { token: admin, body: { ...alta, license_number: 'COL-E2E-1' } });
check('Licencia repetida con otra grafía: 409', dup.status === 409 && dup.json?.code === 'LICENSE_ALREADY_REGISTERED', `${dup.status} ${dup.json?.code}`);

const assoc = await call('POST', `/doctors/${id}/specialties`, { token: admin, body: { specialty_code: 'NEURO', is_primary: false, valid_from: '2026-01-01' } });
check('Asociar NEURO como secundaria: 201', assoc.status === 201 && assoc.json?.is_primary === false, `${assoc.status}`);
const conflict = await call('POST', `/doctors/${id}/specialties`, { token: admin, body: { specialty_code: 'NEURO', is_primary: true, valid_from: '2026-06-01' } });
check('Segunda principal solapada: 409', conflict.status === 409, `${conflict.status} ${conflict.json?.code}`);

// Horario real (lunes 08:00-10:00, bloques de 30 min) y una cita 08:15-08:45: el trigger de doctor_schedules exige la especialidad vigente.
sql(`INSERT INTO doctor_schedules (doctor_profile_id, specialty_id, weekday, start_minute, end_minute, slot_minutes, valid_from)
     VALUES ('${id}', (SELECT specialty_id FROM specialties WHERE code = 'NEURO'), 1, 480, 600, 30, '2026-01-01');
     INSERT INTO local_patients (local_patient_id, global_id, code, given_name, family_name, birth_date, sex) VALUES ('99999999-9999-4999-8999-999999999999', gen_random_uuid(), 'PAC-E2E', 'Paciente', 'Ficticio', '1990-01-01', 'unknown');
     INSERT INTO appointments (local_patient_id, doctor_profile_id, specialty_id, starts_at, ends_at, status, reason)
     VALUES ('99999999-9999-4999-8999-999999999999', '${id}', (SELECT specialty_id FROM specialties WHERE code = 'NEURO'), '2026-10-19 08:15:00+00', '2026-10-19 08:45:00+00', 'confirmed', 'Consulta');`);
const avail = await call('GET', '/availability?specialty=NEURO&date=2026-10-19', { token: medico });
const mine = avail.json?.doctors?.find((d) => d.doctor_id === id);
check('Disponibilidad como medico: la cita 08:15-08:45 bloquea 08:00 y 08:30', avail.status === 200 && JSON.stringify(mine?.slots) === JSON.stringify(['09:00', '09:30']), `${avail.status} · franjas ${JSON.stringify(mine?.slots)}`);
check('La disponibilidad no expone paciente, licencia ni usuario', !/PAC-E2E|COL-E2E|Paciente|local_user_ref|license/i.test(avail.text), `${Object.keys(mine ?? {}).sort().join(', ')}`);

const noPerm = await call('POST', '/doctors', { token: medico, body: alta });
check('medico no puede registrar: 403 RBAC_DENIED', noPerm.status === 403 && noPerm.json?.code === 'RBAC_DENIED', `${noPerm.status} ${noPerm.json?.code}`);
const noToken = await call('GET', '/availability?specialty=NEURO&date=2026-10-19', {});
check('Sin token: 401', noToken.status === 401, `${noToken.status}`);
const foreign = await call('POST', '/doctors', { token: admin, tenant: H2, body: { ...alta, license_number: 'OTRO-1' } });
check('Token del hospital 1 con X-Tenant-ID del hospital 2: rechazado y sin escritura', [401, 403].includes(foreign.status) && sql(`SELECT count(*) FROM doctor_profiles WHERE license_number = 'OTRO-1'`) === '0', `${foreign.status} ${foreign.json?.code ?? foreign.json?.error ?? ''}`);
const bad = await call('GET', '/availability?specialty=NEURO&date=2026-02-30', { token: medico });
check('Fecha imposible: 422 con trace_id', bad.status === 422 && bad.json?.code === 'INVALID_DATE_RANGE' && Boolean(bad.json?.trace_id), `${bad.status} ${bad.json?.code}`);
const big = await call('POST', '/doctors', { token: admin, body: { ...alta, license_number: 'L'.repeat(65) } });
check('Licencia de 65 caracteres: 422', big.status === 422 && big.json?.code === 'PAYLOAD_INVALID', `${big.status} ${big.json?.code}`);

// Errores del cliente que PostgreSQL detecta: antes salían como 500 «base caída».
const notUuid = await call('POST', '/doctors/doc-x/specialties', { token: admin, body: { specialty_code: 'CARD', valid_from: '2026-02-01' } });
check('Id de médico que no es uuid: 404 y no 500', notUuid.status === 404 && notUuid.json?.code === 'DOCTOR_NOT_FOUND', `${notUuid.status} ${notUuid.json?.code}`);
const badRef = await call('POST', '/doctors', { token: admin, body: { ...alta, local_user_ref_id: 'u-ana', license_number: 'NUEVA-1' } });
check('Referencia de usuario que no es uuid: 422 y no 500', badRef.status === 422 && badRef.json?.code === 'PAYLOAD_INVALID', `${badRef.status} ${badRef.json?.code}`);
const noRef = await call('POST', '/doctors', { token: admin, body: { ...alta, local_user_ref_id: '00000000-0000-4000-8000-0000000000aa', license_number: 'NUEVA-2' } });
check('Referencia de usuario inexistente: 422 USER_REF_NOT_FOUND', noRef.status === 422 && noRef.json?.code === 'USER_REF_NOT_FOUND', `${noRef.status} ${noRef.json?.code}`);
const sameUser = await call('POST', '/doctors', { token: admin, body: { ...alta, license_number: 'NUEVA-3' } });
check('Usuario que ya tiene un perfil activo: 409 USER_ALREADY_REGISTERED', sameUser.status === 409 && sameUser.json?.code === 'USER_ALREADY_REGISTERED', `${sameUser.status} ${sameUser.json?.code}`);
check('Ninguno de esos rechazos dejó un perfil', sql(`SELECT count(*) FROM doctor_profiles`) === '1', `perfiles: ${sql('SELECT count(*) FROM doctor_profiles')}`);

let limited = 0;
for (let i = 0; i < 125; i++) { if ((await call('GET', '/availability?specialty=NEURO&date=2026-10-19', { token: medico })).status === 429) { limited++; } }
check('Límite de tasa de la consulta (120/min por usuario): hay 429', limited > 0, `${limited} respuestas 429 en 125 consultas más`);

console.log('| # | Verificación | Resultado | Detalle |\n|---|---|---|---|');
console.log(rows.join('\n'));
console.log(`\n${rows.length - failed} de ${rows.length} verificaciones cumplen.`);
process.exit(failed ? 1 : 0);
