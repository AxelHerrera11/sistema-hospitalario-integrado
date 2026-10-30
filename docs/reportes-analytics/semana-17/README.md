# ASII-23 Semana 17 — Seguridad y despliegue

**Issue:** `Refs #152` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

**Despliegue final: no realizado.** No hay staging ni on-premise autorizados. Esta semana entrega la matriz de amenazas, las pruebas ejecutadas y una instalación local reproducible.

## Matriz de amenazas (STRIDE)

| ID | Tipo | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|---|
| A1 | Suplantación | Usar el API sin sesión o con un token caducado | `jwt.auth` | `IndicatorEndpointTest`: 401 | Mitigada |
| A2 | Suplantación | Token del hospital A con cabecera del hospital B | `tenant` | Recorrido real: 403 `TENANT_MISMATCH` | Mitigada |
| A3 | Elevación | Miembro válido del hospital B contra la base del hospital A | `EnsureHospitalDatabaseTenant` | `test_miembro_de_otro_hospital_…`; recorrido real: 403 sin leer | Mitigada; defecto real corregido en #1561 |
| A4 | Elevación | Rol sin `reportes.ver` | `rbac:reportes.ver` | Recepción y enfermería: 403 | Mitigada |
| A5 | Manipulación | Inyección SQL por `service_code` | Sentencia preparada | Prueba de auditoría sin efecto; `PdoEpisodeSummaryReader` | Mitigada |
| A6 | Manipulación | Tipos inválidos en el cuerpo | Validación en el controlador | 422 con arreglo o número | Mitigada |
| A7 | Revelación | Datos de pacientes en la respuesta | Solo `count` y `SUM` | `test_la_respuesta_y_los_errores_no_exponen_datos_de_pacientes` | Mitigada |
| A8 | Revelación | Detalle interno en errores | Códigos cerrados | Prueba 503 | Mitigada |
| A9 | Revelación | Respuestas en una caché compartida | `no-store, private` | Cabecera comprobada | Mitigada |
| A10 | Revelación | Inferir un paciente con filtros muy pequeños | Ninguno | Una sala con un episodio da 0 o 100 % | **Abierta**; requiere política de tamaño mínimo |
| A11 | Repudio | No se registra quién calculó | Ninguno propio | Sin auditoría de lectura | **Abierta**; decidir con Gobernanza |
| A12 | Denegación | Muchos cálculos seguidos | Ninguno propio | Sin límite de tasa | **Abierta** |
| A13 | Integridad | Cifra falsa con la base caída | 503 sin cifra | `test_una_base_hospital_inexistente_…` | Mitigada |
| A14 | Configuración | Instancia sin `HOSPITAL_DB_TENANT_ID` | El guard responde 503 | Prueba del guard | Mitigada |
| A15 | Datos | Mezcla si varios hospitales usan una conexión | El guard rechaza; falta enrutar | Sigue habiendo una base por instancia | Parcial; Plataforma y Tenants |

## Pruebas de seguridad ejecutadas el 30/10/2026

| Caso | Resultado |
|---|---|
| Sin token | 401 |
| Recepción | 403 `RBAC_DENIED` |
| Token A, cabecera B | 403 `TENANT_MISMATCH` |
| Miembro válido de B | 403 `HOSPITAL_DATABASE_TENANT_MISMATCH` |
| `DELETE` a la ruta | 405 |
| Cadena de inyección en la sala | Sin efecto: N/A |

## Instalación local reproducible

1. PostgreSQL 15 o superior, con dos bases propias (CENTRAL y HOSPITAL) y `CREATE EXTENSION btree_gist;` en HOSPITAL.
2. `cp .env.example .env`, y definir `CENTRAL_DB_*`, `HOSPITAL_DB_*`, `HOSPITAL_DB_TENANT_ID` y `JWT_SECRET`. Generar `APP_KEY` con `php artisan key:generate`.
3. `composer migrate:central`, `composer migrate:framework` y `composer migrate:hospital`, en ese orden.
4. `php artisan auth:provision-first-superadmin`. La contraseña la teclea quien instala y no se guarda en el repositorio.
5. `npm ci`, `npm run build` y `php artisan serve`.

No use bases compartidas ni datos reales. La bitácora está en [`../semana-16/README.md`](../semana-16/README.md#bitácora-de-la-instalación-local).
