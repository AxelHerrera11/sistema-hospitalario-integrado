# Semana 16 — Integración, validación y primera entrega funcional (ASII-09)

Entrega de la semana 16 del módulo **Dashboard de ocupación hospitalaria** según el plan
semanal del repositorio: el módulo deja de ser un Micro-HIS aislado y queda integrado en el API
del HIS, con pantalla propia y evidencia ejecutada.

Esta semana se publica en **dos PR** porque el límite de 400 líneas por PR no permite entregar
integración de backend, interfaz y documentación en un solo PR:

| PR | Alcance |
|---|---|
| #574 | Endpoint `GET /api/v1/occupancy` en Laravel, tenant + JWT + permiso `ocupacion.ver`, alineación de la tasa con el contrato y pruebas del contrato HTTP. |
| Este PR | Pantalla Vue del dashboard, utilidades verificadas con `node`, ruta de navegación y esta documentación. |

## Qué se entrega

- **Backend (PR #574).** El caso de uso `ConsultOccupancy` del Micro-HIS se resuelve por el
  contenedor; la lectura de camas usa el adaptador PDO sobre la conexión `hospital`. La ruta
  `/api/v1/occupancy` exige `X-Tenant-ID`, JWT válido y el permiso `ocupacion.ver`. El corte
  `cutoff_at` es opcional y debe ser ISO-8601 con zona horaria. Los errores usan el sobre del
  contrato: `error.code`, `error.message` y `meta.request_id`.
- **Interfaz (este PR).** `OccupancyPage.vue` consume `GET /api/v1/occupancy` con el cliente axios
  que ya envía el token y `X-Tenant-ID`. Las utilidades puras viven en el módulo JavaScript de
  `resources/js/modules/ocupacion/` y se verifican con `node`, sin navegador.
- **Corrección de dominio.** La tasa de ocupación se calcula sobre camas operativas
  (`inventario − mantenimiento − inactivas`), como define el contrato vigente; antes se usaba
  solo el denominador `ocupadas + disponibles`.

## Reglas de la pantalla

Carga: mensaje `role="status"` y botón deshabilitado. Acceso denegado: mensaje explícito, sin
ceros. Servicio caído: se conserva la última fotografía conocida y se marca desactualizada. Sin
salas: mensaje de inventario vacío. Corte con más de 15 minutos: fotografía desactualizada. La
pantalla nunca muestra pacientes, solo camas, salas y conteos.

El control por permiso no se replica en el frontend: la API de autenticación todavía no entrega
los permisos verificados del usuario (`resources/js/router/permissions.js` falla de forma segura),
por lo que la autoridad permanece en el backend.

## Evidencia ejecutada

```bash
# comando: node --test "resources/js/modules/ocupacion/*.test.mjs"
# resultado (21/10/2026, integradora): tests 1, pass 1, fail 0; el archivo de prueba, ejecutado
#   directamente con node, imprime «OK: 14 aserciones de la pantalla de ocupación aprobadas»

# comando: npm run build
# resultado: ✓ 95 modules transformed; OccupancyPage-Bt9v-GoS.js 4.26 kB

# comando: php vendor/bin/phpunit tests/Feature/Ocupacion/OccupancyEndpointTest.php
# resultado: OK (8 tests, 27 assertions)   [PR #574]

# comando: php vendor/bin/phpunit --testsuite Database
# resultado: OK (59 tests, 738 assertions) sobre PostgreSQL 16   [PR #574]

# comando: php micro-his/ocupacion-hospitalaria/tests/run.php
# resultado: OK: 12 verificaciones aprobadas   [PR #574]
```

Captura real de la fotografía calculada contra el esquema HOSPITAL migrado (PR #574):

```json
{
  "cutoff_at": "2026-10-20T10:00:00-06:00",
  "global": {
    "occupied": 1, "available": 0, "maintenance": 1, "inactive": 0,
    "unverified_occupied": 1, "total_beds": 3, "inconsistencies": 2, "occupancy_rate": 50
  },
  "wards": [{ "ward_code": "UCI", "total_beds": 3, "occupancy_rate": 50 }]
}
```

## Dependencias y pendientes

| # | Pendiente | Responsable | Estado |
|---:|---|---|---|
| 1 | `App\Models\Tenant` y `App\Models\User` no existen en `develop`: el middleware `tenant` no puede resolver el hospital y la ruta no puede ejecutarse contra un hospital real. | ASII-25 (Tenants, PR #501 y #502 abiertos) | PENDIENTE |
| 2 | `ocupacion.ver` se activó en la matriz compartida `config/rbac.php`; falta ratificación o corrección de ASII-02 (RBAC). | ASII-02 (RBAC) | PENDIENTE |
| 3 | Formato común de errores: el endpoint usa `error.code` y el middleware RBAC usa `code` + `trace_id`. | Lis (contratos) | PENDIENTE |
| 4 | Corte por defecto: hoy es la hora del servidor; falta decidir si es un cierre de turno. | ASII-06 (camas) | PENDIENTE |
| 5 | Permisos verificados en el payload de autenticación, para que el frontend pueda evaluar `can()`. | ASII-25 / ASII-02 | PENDIENTE |
| 6 | Evidencia en entorno de integración (staging/on-premise): depende del punto 1. | Javier, tras el punto 1 | PENDIENTE |

Sin el punto 1 no es posible levantar la evidencia de staging que pide el enunciado de la semana 16;
por eso queda declarado como dependencia externa y no como un fallo de este módulo.

## Fuera de alcance

Asignar, trasladar o liberar camas (el módulo es de consulta agregada), crear tablas nuevas (se
usan `wards`, `beds` y `bed_stays` de la base HOSPITAL) y sustituir la autoridad del backend: la
pantalla solo presenta lo que el servidor autoriza.
