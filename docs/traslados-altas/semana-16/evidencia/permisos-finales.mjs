// Matriz final de traslados y altas con JWT, tenant, RBAC y PostgreSQL reales; sin activación simulada. Solo bases desechables.
// Uso: E2E_PASSWORD=... PSQL=... CENTRAL_PORT=... CENTRAL_DB=... HOSPITAL_PORT=... HOSPITAL_DB=... node permisos-finales.mjs <url-base> <hospital-a> <hospital-b>
import { execFileSync } from 'node:child_process';

const [base, A, B] = process.argv.slice(2);
const { E2E_PASSWORD: pass, PSQL, CENTRAL_PORT, CENTRAL_DB, HOSPITAL_PORT, HOSPITAL_DB } = process.env;
const U = '00000000-0000-4000-8000-000000000';
const sql = (port, db, q) => execFileSync(PSQL, ['-h', '127.0.0.1', '-p', port, '-U', 'postgres', '-d', db, '-Atc', q], { encoding: 'utf8' }).trim();
const central = (q) => sql(CENTRAL_PORT, CENTRAL_DB, q), hospital = (q) => sql(HOSPITAL_PORT, HOSPITAL_DB, q);
const results = [];
const check = (name, ok, detail = '') => { results.push(ok); console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? ` — ${detail}` : ''}`); };
const login = async (email, t = A) => (await (await fetch(`${base}/api/v1/auth/login`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Tenant-ID': t }, body: JSON.stringify({ email, password: pass }) })).json()).access_token;
const api = async (token, method, path, body, t = A) => { const r = await fetch(`${base}/api/v1${path}`, { method, headers: { 'Content-Type': 'application/json', 'X-Tenant-ID': t, Authorization: `Bearer ${token}` }, body: body ? JSON.stringify(body) : undefined }); return { s: r.status, j: await r.json().catch(() => null) }; };
const consultar = (tok, est, t) => api(tok, 'GET', `/estancias/${est}/internacion`, undefined, t);
const trasladar = (tok, est, cama, t) => api(tok, 'POST', '/traslados', { estancia_esperada_id: est, cama_destino_id: cama, motivo: 'Prueba' }, t);
const alta = (tok, est, t) => api(tok, 'POST', '/altas', { estancia_esperada_id: est }, t);
const huella = () => hospital("SELECT (SELECT count(*) FROM bed_stays WHERE ended_at IS NULL), (SELECT count(*) FROM care_episodes WHERE status = 'open'), (SELECT count(*) FROM bed_stays)");
const code = (r) => r.j?.code ?? r.j?.error ?? r.j?.codigo ?? r.j?.error?.code;

const est1 = `${U}601`, est2 = `${U}602`, est3 = `${U}603`, libre1 = `${U}405`, libre2 = `${U}411`;
const enf = await login('enfermera@a.local'), med = await login('medico@a.local');

// 1. Roles sin concesión automática, con la matriz real.
for (const [correo, rol] of [['admin@a.local', 'admin_hospital'], ['super@a.local', 'superadmin'], ['recepcion@a.local', 'recepcion']]) {
    const t = await login(correo), antes = huella();
    const c = await consultar(t, est1), tr = await trasladar(t, est1, libre1), al = await alta(t, est1);
    check(`${rol}: 403 RBAC_DENIED al consultar, trasladar y dar de alta`, [c, tr, al].every((r) => r.s === 403 && code(r) === 'RBAC_DENIED') && antes === huella());
}
check('enfermería: consulta', (await consultar(enf, est1)).s === 200);
let r = await alta(enf, est1);
check('enfermería: no da de alta', r.s === 403 && code(r) === 'RBAC_DENIED');
check('médico: consulta', (await consultar(med, est1)).s === 200);
r = await trasladar(med, est1, libre1);
check('médico: no traslada', r.s === 403 && code(r) === 'RBAC_DENIED');

// 2. Acceso cruzado.
const antes = huella();
const medB = await login('medico@b.local', B), enfB = await login('enfermera@b.local', B);
r = await consultar(medB, est1, B);
check('miembro de otro hospital: no consulta', r.s === 403 && code(r) === 'HOSPITAL_DATABASE_TENANT_MISMATCH', `${r.s} ${code(r)}`);
r = await trasladar(enfB, est1, libre1, B);
check('miembro de otro hospital: no traslada', r.s === 403 && code(r) === 'HOSPITAL_DATABASE_TENANT_MISMATCH', `${r.s} ${code(r)}`);
r = await alta(medB, est1, B);
check('miembro de otro hospital: no da de alta', r.s === 403 && code(r) === 'HOSPITAL_DATABASE_TENANT_MISMATCH', `${r.s} ${code(r)}`);
r = await alta(med, est1, B);
check('token del hospital A con cabecera del hospital B: rechazado', r.s === 403 && code(r) === 'TENANT_MISMATCH', `${r.s} ${code(r)}`);
check('el acceso cruzado no escribió nada', antes === huella());

// 3. Flujo traslado → alta.
r = await trasladar(enf, est1, libre1);
const nueva = r.j?.estancia_destino_id;
check('enfermería traslada', r.s === 201 && Boolean(nueva));
check('la estancia de origen queda cerrada', (await consultar(med, est1)).j?.estancia_consultada_abierta === false);
r = await alta(med, nueva);
check('médico sin asignación con el paciente da el alta', r.s === 200 && r.j?.resultado === 'exito', `${r.s}`);
check('episodio cerrado y cama liberada', hospital(`SELECT e.status, b.status FROM bed_stays s JOIN care_episodes e USING (care_episode_id) JOIN beds b USING (bed_id) WHERE s.bed_stay_id = '${nueva}'`) === 'closed|available');
r = await alta(med, nueva);
check('repetir el alta: 409', r.s === 409 && r.j?.motivo === 'EPISODIO_NO_ABIERTO');

// 4. Permiso vigente: se comprueba en cada petición, también con un token ya emitido.
const medicoId = central("SELECT user_id FROM central_users WHERE email = 'medico@a.local'");
const membresia = `user_id = '${medicoId}' AND hospital_id = '${A}'`;
const huellaEst = () => hospital(`SELECT ended_at IS NULL FROM bed_stays WHERE bed_stay_id = '${est2}'`);
central(`UPDATE hospital_memberships SET valid_to = now() - interval '1 second', valid_from = now() - interval '1 day' WHERE ${membresia}`);
r = await alta(med, est2);
check('membresía vencida: el token vigente ya no da de alta', r.s === 403 && huellaEst() === 't', `${r.s} ${code(r)}`);
central(`UPDATE hospital_memberships SET valid_to = NULL, status = 'inactive' WHERE ${membresia}`);
r = await alta(med, est2);
check('membresía inactiva: no da de alta', r.s === 403 && huellaEst() === 't', `${r.s} ${code(r)}`);
central(`UPDATE hospital_memberships SET status = 'active' WHERE ${membresia}`);
const ref = `central_user_id = '${medicoId}'`;
const refEstado = hospital(`SELECT status FROM local_user_refs WHERE ${ref}`);
hospital(`UPDATE local_user_refs SET status = 'inactive' WHERE ${ref}`);
r = await alta(med, est2);
check('sin referencia local activa: no da de alta', r.s === 403 && r.j?.codigo === 'ACTOR_SIN_REFERENCIA_LOCAL' && huellaEst() === 't', `${r.s} ${code(r)}`);
hospital(`UPDATE local_user_refs SET status = '${refEstado}' WHERE ${ref}`);
r = await alta(med, est3);
check('con permiso vigente otra vez: da el alta', r.s === 200, `${r.s}`);

console.log(`${results.filter(Boolean).length}/${results.length} comprobaciones`);
process.exit(results.every(Boolean) ? 0 : 1);
