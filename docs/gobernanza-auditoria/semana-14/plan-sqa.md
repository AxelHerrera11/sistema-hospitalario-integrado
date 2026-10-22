# Semana 14 — Plan de aseguramiento de calidad (SQA)

## 1. Objetivos

1. Que ningún evento de seguridad se pierda en silencio y que la consulta no filtre datos entre tenants.
2. Que cada requisito M tenga al menos una prueba automática que pase.
3. Mantener la arquitectura por capas y los adaptadores intercambiables (RNF-GOB-08).
4. Detectar y dar seguimiento a los defectos hasta cerrarlos o aceptarlos explícitamente.

## 2. Alcance

| Incluido | Excluido |
|---|---|
| `micro-his/gobernanza-auditoria/**`, `app/GobernanzaAuditoria/**`, prototipo, documentación del módulo y propuesta SQL. | Código de RBAC, de Tenants y migraciones (se verifican solo sus interfaces). Infraestructura de despliegue (Semanas 16–17). |

## 3. Métricas

**Columna «Tipo»:** **Medida** = calculada con datos reales de este repositorio el 2026-10-20; **Objetivo** = umbral propuesto que todavía no se puede medir en este entorno.

| Id | Métrica | Método de cálculo | Umbral | Justificación | Tipo | Valor actual |
|---|---|---|---|---|---|---|
| M-01 | Tasa de aprobación de pruebas | `OK / (OK + FALLO)` sobre la salida de `tests/run.php` con las dependencias de develop | 100 % para fusionar | La bitácora es un control de seguridad: no se aceptan fallos conocidos. | Medida | **112/112 = 100 %** ([evidencia](../evidencias/s15-ejecucion-suites-rbac-develop.txt)); en local, 106/106 con 6 omitidas por dependencias. |
| M-02 | Cobertura de requisitos con prueba automática | Requisitos RF/RNF con ≥ 1 caso automático aprobado ÷ total de requisitos ([matriz](../semana-15/matriz-pruebas.md)) | 100 % de los M; ≥ 85 % del total | Todo criterio de aceptación debe ser verificable (Semana 2). | Medida | **18/20 = 90 %**; los M: **15/15 = 100 %** (RNF-GOB-09 y RNF-GOB-10 no son automáticos). |
| M-03 | Archivos que superan 400 líneas | `find … -exec wc -l` sobre los archivos del módulo (php, md, js, css, html, mmd, sql) | 0 | Requisito del curso; mejora la revisión. | Medida | **0** (el mayor archivo de código es `PdoAuditLog.php`, con 198 líneas) |
| M-04 | Violaciones de dependencia entre capas | TC-A-01…03 (análisis estático por expresiones regulares) | 0 | Conserva DIP/OCP y permite sustituir adaptadores. | Medida | **0** |
| M-05 | Defectos abiertos por severidad | Hallazgos de la [Semana 13](../semana-13/registro-hallazgos.md) en estado Pendiente o Externo | Alta = 0 en el código propio; Alta externa con responsable asignado | Un defecto alto propio bloquea la entrega. | Medida | Propios abiertos: Alta 0, Media 1 (H-GOB-02), Baja 1. Externos: Alta 1 (H-GOB-07), Media 1. |
| M-06 | Cobertura de líneas de Domain y Application | `phpdbg -qrr` o PCOV/Xdebug sobre `tests/run.php` | ≥ 85 % | Son las capas con las reglas de negocio. | **Objetivo** | **No medida:** el PHP 8.2.12 del entorno no tiene Xdebug ni PCOV (`php -m`). |
| M-07 | Latencia p95 de `GET /audit-events` (página de 100, 1 M de filas) | `EXPLAIN (ANALYZE)` y medición repetida en PostgreSQL con datos de volumen | < 500 ms | RNF-GOB-09. | **Objetivo** | **No medida:** no hay PostgreSQL ni datos de volumen en este entorno. |

## 4. Riesgos y controles

| Riesgo | Prob. | Impacto | Control | Métrica |
|---|---|---|---|---|
| R-01: la cadena de Tenants y la de Gobernanza divergen. | Media | Alto | TC-R-01…03 en cada cambio del hasher; propuesta DU-01. | M-01 |
| R-02: fuga entre tenants por un filtro nuevo. | Baja | Alto | AT-04 (el tenant es un argumento obligatorio); TC-C-01, TC-N-02 y TC-N-07…09; revisión del punto 4 del checklist. | M-01, M-02 |
| R-03: se pierde un evento Security sin que nadie lo note. | Baja | Alto | `RecordAuditEvent` relanza; TC-U-17, TC-N-17, TC-I-03 y H-GOB-04. | M-01 |
| R-04: dependencias externas no disponibles (modelos, permiso, esquema). | Alta | Medio | Adaptadores InMemory y fixtures; plan de la Semana 5 §8; seguimiento de H-GOB-07/11. | M-05 |
| R-05: contención por el bloqueo de la cadena. | Media | Medio | Propuesta de cadena por hospital (H-GOB-02); medición pendiente. | M-07 |
| R-06: datos sensibles en el contexto. | Media | Alto | `ContextSanitizer` y saneamiento también al leer; TC-U-08/09, TC-W-14/15. | M-01 |

## 5. Responsables

| Rol | Persona | Responsabilidad |
|---|---|---|
| Responsable del módulo | Asignación en README: #22 `bquiroao`. Esta entrega la preparó `cindyruano`. **El equipo debe confirmar quién la asume.** | Ejecutar la estrategia y cerrar los defectos propios. |
| Esquema y migraciones | Javier (`Javifa2324`) | Propuesta SQL 001, H-GOB-02 y M-07. |
| RBAC | María (`Mar-03`) | `auditoria.ver` y H-GOB-11. |
| Usuarios/Tenants | Cindy (`cindyruano`) | H-GOB-07, `audit_log`, DU-01. |
| QA transversal | Según README (#25, #28) | Revisión por pares y regresión del grupo. |

## 6. Estrategia de pruebas

| Nivel | Herramienta | Suites | Cuándo |
|---|---|---|---|
| Unitarias (Domain y Application) | PHP vanilla con un arnés de `check()`, igual que `micro-his/rbac/tests` | `unit.php` | Cada cambio |
| Caja blanca (ramas de PDO y de la cadena) | Ídem, con SQLite en memoria | `whitebox.php` | Cada cambio en Persistence |
| Contrato y sustitución | Ídem, InMemory y PDO | `contract.php` | Cada cambio de adaptador |
| Caja negra (HTTP) | Ídem, sobre `AuditHttpHandler` | `blackbox.php` | Cada cambio de contrato |
| Integración | RBAC real (develop) y Tenants real (rama) | `rbac_integration.php`, `hash_compat.php` | Antes de fusionar |
| Laravel | `Illuminate\Http\Request` y el contenedor real | `laravel_glue.php` | Antes de fusionar |
| Arquitectura | Expresiones regulares sobre `src/` | `architecture.php` | Cada cambio |
| De punta a punta con PostgreSQL | `php artisan test` (PHPUnit del proyecto) | **Pendiente** de crear cuando existan `audit_log`, los modelos y RBAC (Semana 15 §5) | Semana 16 |

## 7. Criterios de aceptación de la entrega

1. M-01 = 100 % con las dependencias de develop.
2. M-02: 100 % de los requisitos M.
3. M-03 = 0 y M-04 = 0.
4. M-05: 0 defectos altos propios abiertos.
5. Ningún archivo de otro módulo modificado (comprobado con `git status`).
6. Documentación sincronizada con el código (los identificadores de prueba existen en la salida real).

**Estado al 2026-10-20:** se cumplen los criterios 1 a 6. M-06 y M-07 quedan como objetivos sin medir.

## 8. Registro de defectos y seguimiento

- **Registro:** [Semana 13 — registro-hallazgos.md](../semana-13/registro-hallazgos.md) (H-GOB-xx) y [Semana 9](../semana-09/evaluacion-usabilidad-accesibilidad.md) (F-xx).
- **Ciclo:** Abierto → En corrección → Corregido (con una prueba de regresión que falle sin el cambio, cuando se pueda verificar) → Cerrado; o bien Aceptado (riesgo documentado) o Externo (con responsable).
- **Defecto de seguridad (permisos, tenant o datos sensibles):** se informa sin detalle explotable al responsable del módulo afectado.
