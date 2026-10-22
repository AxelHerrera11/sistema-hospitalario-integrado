# Pruebas de caja negra

Se prueba el comportamiento observable del endpoint `GET /api/v1/occupancy` sin usar su
implementación interna. Las pruebas viven en `tests/Feature/Ocupacion/` y se ejecutan contra
Laravel con PostgreSQL 16 real.

## Particiones de equivalencia

| # | Clase de entrada | Ejemplo | Resultado esperado |
|---|---|---|---|
| BN-01 | Credenciales y hospital válidos, método `GET` | JWT válido, `X-Tenant-ID` del hospital autorizado | `200` con `data.cutoff_at`, `data.global` y `data.wards`, y `meta.request_id` |
| BN-02 | Sin cabecera `Authorization` | — | `401`, sin cifras |
| BN-03 | Token inválido o expirado | JWT con firma inválida | `401`, sin cifras |
| BN-04 | Token válido sin el permiso `ocupacion.ver` | Rol sin el permiso | `403` `RBAC_DENIED`, sin cifras |
| BN-05 | Hospital declarado distinto del autorizado | `X-Tenant-ID` de otro hospital | `403` `TENANT_MISMATCH`, sin cifras |
| BN-06 | Método distinto de `GET` | `POST /api/v1/occupancy` | `405`, sin cifras |
| BN-07 | `cutoff_at` con formato no canónico | `2026-10-14 10:00` | `400`, sin cifras |
| BN-08 | Base HOSPITAL caída o inexistente | DSN a una base inexistente | `503`, sin cuerpo de ocupación |

## Casos límite

| # | Límite | Entrada | Resultado esperado |
|---|---|---|---|
| BN-05a | Inicio exacto de la estancia | Corte igual a `started_at` | La cama **está** ocupada |
| BN-05b | Fin exacto de la estancia | Corte igual a `ended_at` | La cama **está** libre (intervalo `[inicio, fin)`) |
| BN-05c | Antes del inicio | Corte un segundo antes | La cama está libre |
| BN-05d | Después del fin | Corte un segundo después | La cama está libre |
| BN-09 | Capacidad operativa cero | Hospital sin camas operativas | Cantidades en cero y `occupancy_rate` en `null` |
| BN-10 | Inventario vacío | Hospital sin camas | Total cero y tasa `null`, sin `NaN` ni `0 %` |

## Permisos y aislamiento

| # | Caso | Resultado esperado |
|---|---|---|
| BN-11 | Un usuario del hospital A consulta con `X-Tenant-ID` del hospital B | `403` y ningún dato del hospital B |
| BN-12 | El mismo token con otro `X-Tenant-ID` en dos llamadas seguidas | Respuestas idénticas y coherentes con un único corte |
| BN-13 | Revocación del permiso durante la sesión | La siguiente consulta responde `403` y el cliente descarta lo que mostraba |

## Privacidad y robustez de la respuesta

| # | Caso | Resultado esperado |
|---|---|---|
| BN-14 | Revisión del cuerpo de la respuesta | No contiene nombre, documento, diagnóstico, identificador de paciente ni de episodio |
| BN-15 | Revisión de los cuerpos de error | No contienen `SQLSTATE`, DSN, traza ni ruta interna |
| BN-16 | Cabeceras de la respuesta | `Cache-Control: no-store`; el `X-Request-ID` recibido se sanea |
| BN-17 | Método de solo lectura | El endpoint no escribe en la base: el conteo de filas no cambia tras la consulta |

## Comandos

```bash
$ php artisan test tests/Feature/Ocupacion
Tests:    16 passed (66 assertions)
Duration: 0.59s

$ php artisan test tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
Tests:    6 passed (59 assertions)
Duration: 12.37s
```

Ambos comandos se ejecutan contra PostgreSQL 16 en los contenedores `bd-central` y `bd-hospital`
con las migraciones de `origin/develop` aplicadas.

## Lo que no cubre la caja negra

- El filtro por sala (RF-OCC-05) no forma parte del contrato v1 publicado.
- El comportamiento con 10 000 camas no se midió: sin volumen, el dato se reporta como «sin
  datos».
- No se probó la sesión del cliente Vue contra la API real dentro de la suite: esa integración se
  verificó con evidencia de despliegue en la semana 17 (login `200`, ocupación `200` con datos
  reales, `401`, `403` por tenant, `403` por permiso y `503` con la base caída).
