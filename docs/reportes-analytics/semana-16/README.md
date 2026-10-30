# ASII-23 Semana 16 — Integración y primera entrega funcional

**Issue:** `Refs #152` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

## Estado

| Aspecto | Estado |
|---|---|
| Integrado en el sistema compartido | Sí: pantalla Vue, API v1, `tenant`, `jwt.auth`, guard de base, RBAC y PostgreSQL HOSPITAL |
| Funcionando en servidor, staging u on-premise | **No.** No hay un entorno autorizado. Solo se probó en local |
| Revisión humana | Sin registrar |

## Evidencia de integración

| Prueba | Qué comprueba | Resultado (30/10/2026) |
|---|---|---|
| `tests/Feature/Reportes` | Contrato HTTP, permisos y base real | 16 correctas |
| `semana-11/evidencia/recorrido-e2e.mjs` | Chrome, API, JWT, RBAC y PostgreSQL | 15/15 |
| `php artisan test` | Regresión del sistema | 1 falla ajena (PublishedContractsTest, por diagnosticos.png de ASII-11); 546 correctas de 547 |

## Errores corregidos durante la integración

| Error | Causa | Corrección | PR |
|---|---|---|---|
| Base caída respondía 500 | La conexión se abría fuera del `try` | Resolver el caso de uso dentro del `try` | #1269 |
| Prueba dependía de filas previas | Faltaba semilla propia | Expediente sintético en la transacción | S15 |
| Miembro de otro hospital leía el indicador | La ruta no tenía guard de base | `EnsureHospitalDatabaseTenant` | #1561 |
| Fechas imposibles aceptadas | Desbordamiento de `createFromFormat` | Comprobación de ida y vuelta | #1565 |
| Cifra anterior junto a un error | Se limpiaba después de validar | `limpiar()` primero | #1565 |

## Bitácora de la instalación local

Ejecutada el 30/10/2026 en una copia aislada, con bases propias y datos ficticios.

| Paso | Comando | Resultado |
|---|---|---|
| 1 | `initdb` de dos clústeres en puertos propios y `CREATE EXTENSION btree_gist` en HOSPITAL | Correcto |
| 2 | Migraciones CENTRAL, framework y HOSPITAL con `--database` explícito | Correcto |
| 3 | Sembrado de hospitales A y B, usuarios y episodios ficticios | Correcto |
| 4 | `HOSPITAL_DB_TENANT_ID` con el hospital A; `npm run build`; `php artisan serve` | Correcto |
| 5 | Recorrido en Chrome | 15/15 |

## Dependencias abiertas

| Dependencia | Responsable | Acción |
|---|---|---|
| Entorno de staging u on-premise | Coordinación del curso | Autorizar y documentar el entorno |
| Elegir la base HOSPITAL por hospital | Plataforma y Tenants | Resolver `clinical_endpoint` |
| Quién escribe `cancelled` en `care_episodes` | ASII-07 y ASII-08 | Acordar la operación |
