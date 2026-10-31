# Walkthrough de defensa — ASII-06 (5–8 minutos)

## 0:00–0:45 — Alcance y honestidad de evidencia

“La plantilla del curso no especifica Semana 12; el issue #11 tampoco. Uso
como referencia el formato de segunda evaluación parcial de Pacientes. Voy a
distinguir lo implementado y probado de la autenticación local que aún requiere
una identidad autorizada.”

Abrir [README](./README.md) y mostrar las etiquetas de estado. Aclarar que la
defensa no amplía el catálogo a funcionalidad clínica.

## 0:45–1:45 — Prototipo y contrato

Mostrar la ruta `/wards` y explicar la consulta de wards y la selección para
cargar camas. Abrir
[`WardCatalogPage.vue`](../../../resources/js/modules/salas-camas/pages/WardCatalogPage.vue)
y recorrer carga, vacío, error, reintento y control de respuesta obsoleta.

Enumerar campos reales de ward y bed. Explicar que el listado de camas usa el
`ward_id` seleccionado y que un `200` vacío no es un error.

## 1:45–2:45 — Seguridad y autenticación

Mostrar `SalasCamasServiceProvider`, `TenantMiddleware`, `JwtAuth` y
`RequirePermission`. Explicar la secuencia:

```text
X-Tenant-ID UUID → hospital existente/activo → JWT con el mismo tenant
→ membresía activa/vigente → RBAC camas.ver → catálogo
```

La ruta frontend pide permiso efectivo, pero la decisión autoritativa es el
servidor. `user.permissions` se calcula desde la membresía del tenant en
`AuthenticateUser`.

## 2:45–3:45 — Explicar el error local

Abrir [diagnóstico](./diagnostico-integracion-local.md). Si el texto observado
fue exactamente “El hospital indicado no es válido.”, el código lo devuelve
cuando el valor de `X-Tenant-ID` no tiene formato UUID. Si el UUID tiene forma
correcta pero no está en el registro central, el código distinto es
`HOSPITAL_NOT_FOUND`; hospital suspendido/inactivo produce
`HOSPITAL_INACTIVE`.

No mostrar el contenido de `localStorage`, `.env`, tokens, correo ni
contraseña. Pedir un UUID de hospital activo de fuente administrativa
autorizada y usar la cuenta de pruebas asignada; no probar credenciales por
adivinación.

## 3:45–4:45 — Pruebas automatizadas

Presentar la salida de:

- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php`
- `php vendor/phpunit/phpunit/phpunit tests/Feature/Tenants/IdentityContextTest.php`
- `node --test tests/Frontend/WardCatalogNavigationTest.mjs`
- `php micro-his/salas-camas/tests/run.php`
- `npm run build`

Precisar: la prueba de identidad configura SQLite en memoria; la integración
PDO HOSPITAL usa una transacción y confirma rollback; la prueba automatizada no
equivale a login real en navegador.

## 4:45–5:45 — Estado PostgreSQL y tenancy

Reportar `SELECT 1` exitoso en las conexiones configuradas y la integración
hospitalaria con rollback. El test no valida identidad de usuario contra el
registro central real. Explicar que el tenant middleware valida hospital, pero
la conexión del catálogo aún es fija y no existe aislamiento
multi-tenant completo.

## 5:45–6:30 — Cierre y verificación siguiente

Solicitar, por canal seguro y fuera del repositorio:

1. `hospital_id` (UUID) de un hospital existente y activo en la base CENTRAL.
2. Cuenta de pruebas activa con membresía vigente para ese mismo hospital.
3. Confirmación de que el rol efectivo de esa membresía recibe `camas.ver`.

Después iniciar sesión y abrir `/wards`; verificar `GET /api/v1/auth/me`,
`GET /api/v1/wards` y seleccionar una ward para llamar su ruta beds. No cambiar
código o middleware para omitir esos controles.
