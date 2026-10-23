# Presentación — ASII-06 Catálogo de wards y camas

Guion para una defensa de segunda evaluación parcial. Las afirmaciones se
limitan a los archivos y pruebas de esta rama.

## Diapositiva 1 — Módulo y alcance

- **Objetivo:** consultar la estructura de wards y sus camas en el HIS.
- **Entidades:** `wards` y `beds`; no se crea `rooms`.
- **Límite clínico:** el catálogo no registra pacientes ni implementa
  ocupación, estancias, admisiones, traslados, altas o asignación.
- **Estado:** Semana 12 no está descrita en la plantilla general ni en issue
  #11; se presenta evidencia de estado como en el precedente de Semana 12 de
  ASII-03, sin afirmar que ese precedente sea requisito oficial de ASII-06.

## Diapositiva 2 — Flujo funcional implementado

```text
Menú protegido → /wards → GET /api/v1/wards
               → seleccionar ward
               → GET /api/v1/wards/{ward}/beds
```

- **IMPLEMENTADO:** selección de ward y consulta de camas.
- **IMPLEMENTADO:** carga, lista vacía, error seguro y reintento.
- La vista usa los endpoints reales del módulo, no un servicio Vue separado ni
  datos de catálogo ficticios.

## Diapositiva 3 — Contratos y datos mostrados

- Wards: `ward_id`, `code`, `name`, `location`, `status`.
- Beds: `bed_id`, `ward_id`, `code`, `type`, `status`.
- Ward sin camas: `200` con `data: []`.
- Ward inexistente o UUID inválido en consulta de camas:
  `404 WARD_NOT_FOUND`.
- Error de persistencia: `503` con código seguro del catálogo.
- La cama se filtra por `beds.ward_id`. El estado `occupied` se muestra como
  dato operativo del catálogo, no se interpreta como paciente o estancia.

## Diapositiva 4 — Arquitectura y seguridad

```text
Vue /wards
  → cliente Axios compartido
  → api → tenant → jwt.auth → rbac:camas.ver
  → WardCatalogController
  → CatalogService → repositorios → PDO
```

- La ruta frontend y la navegación requieren `camas.ver`; permisos ausentes
  se niegan en el cliente.
- El backend vuelve a autorizar cada petición; ocultar menú no es un control
  de seguridad.
- El middleware requiere `X-Tenant-ID`, un UUID de hospital existente y
  activo. JWT y tenant deben coincidir.
- La matriz activa concede `camas.ver` a `superadmin` y `recepcion`; la
  membresía debe estar activa y vigente.

## Diapositiva 5 — Estado comprobado de pruebas

- Build de frontend: `npm run build` — PASS, 134 módulos Vite.
- Navegación/permisos: Node — PASS, 2 tests.
- Feature API: PHPUnit — PASS, 13 tests, 54 assertions, incluidos errores de tenant.
- Integración PDO: PostgreSQL — PASS, 1 test, 23 assertions y rollback.
- Identidad tenant/JWT/RBAC: PHPUnit — PASS, 11 tests, 75 assertions con
  almacenamiento SQLite en memoria.
- Micro-HIS: PASS, 4 escenarios.
- Rutas API: PASS, 25 rutas registradas.
- No se ejecutó un login con usuario real ni se hizo una inspección visual
  manual en navegador.

## Diapositiva 6 — Diagnóstico del acceso local

- `TenantMiddleware` responde `400 TENANT_INVALID` con
  “El hospital indicado no es válido.” si el valor de `X-Tenant-ID` no es UUID.
- Si el UUID no corresponde a una fila `hospitals.hospital_id`, responde
  `404 HOSPITAL_NOT_FOUND`; si el hospital no está activo, `403
  HOSPITAL_INACTIVE`.
- El usuario necesita `central_users.status=active`, credenciales válidas y
  membresía `hospital_memberships` activa, vigente y para el mismo hospital.
- En Semana 11 la matriz RBAC devuelve permisos efectivos en `user.permissions`;
  para ver el catálogo se necesita `camas.ver`.
- **No se inspeccionaron ni publican IDs, emails, contraseñas o tokens.** Usar
  el UUID vigente entregado por una fuente administrativa autorizada; no
  adivinarlo ni desactivar middleware.

## Diapositiva 7 — PostgreSQL y tenancy

- `SELECT 1` fue exitoso en las conexiones `central` y `hospital`.
- El test PostgreSQL del catálogo verificó la conexión de test `hospital`,
  creación y consulta de ward/camas, relación, aislamiento de la consulta entre
  wards y limpieza por rollback.
- No se consultó el registro de hospitales ni se autenticó con usuarios reales.
- El catálogo usa conexión `hospital` fija; el tenant se valida, pero no
  selecciona dinámicamente la base del hospital. Las tablas no tienen
  `tenant_id`; no afirmar aislamiento multi-tenant completo.

## Diapositiva 8 — Cierre

- La consulta funcional está disponible en código y conectada a los endpoints.
- La autorización de backend y el contrato de tenant están preservados.
- La automatización cubre contratos y PDO con tests aislados/transaccionales.
- El siguiente paso de verificación local requiere que la persona responsable
  proporcione por canal seguro el `hospital_id` activo y una cuenta de pruebas
  autorizada, o que un administrador los verifique en el registro central.
- No crear ni incluir credenciales en la documentación, el código o el PR.
