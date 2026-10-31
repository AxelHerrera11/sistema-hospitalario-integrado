# Semana 17 — Seguridad y despliegue final

**Módulo:** QA, pruebas E2E, CI y guía de despliegue final · **Adaptación:** gestión de ejecuciones automatizadas y
calidad de versiones.

**Tarea:** matriz de amenazas del pipeline y de la gestión de ejecuciones, pruebas de permisos y de datos sensibles,
y bitácora del despliegue final.

## Entregables

| Archivo | Contenido |
|---|---|
| [`matriz-amenazas.md`](matriz-amenazas.md) | 14 amenazas STRIDE del CI, las evidencias, el micro-HIS y el despliegue |
| [`bitacora-despliegue.md`](bitacora-despliegue.md) | Ensayo paso a paso de la guía de despliegue y de los despliegues de micro-HIS con rollback |
| [`evidencias/01-pruebas-seguridad.txt`](evidencias/01-pruebas-seguridad.txt) | Escáner de secretos, permisos del micro-HIS y revisión del workflow |
| [`evidencias/02-ensayo-guia-laravel.txt`](evidencias/02-ensayo-guia-laravel.txt) | Salida del ensayo de la guía, con la hora de cada paso |

## Código nuevo

| Archivo | Para qué |
|---|---|
| `micro-his/qa-calidad-versiones/bin/escanear-secretos.php` | Revisa la carpeta de evidencias antes de conservarla: JWT, claves privadas, `APP_KEY` reales, `Bearer`, contraseñas, URL con credenciales y correos de dominios reales. Informa archivo, línea y tipo, nunca el valor |
| `micro-his/qa-calidad-versiones/bin/desplegar-micro-his.sh` | Despliegue on-premise de un micro-HIS: release inmutable por commit, datos en `shared/`, `php -l`, salud y rollback automático |
| `micro-his/qa-calidad-versiones/tests/ci.php` (CI22, CI23) | Controles negativo y positivo del escáner |

## Resultados (2026-10-29)

| Verificación | Resultado |
|---|---|
| Suites del micro-HIS (`tests/run.php`) | 7 suites aprobadas; `ci` 23/23 |
| Escáner sobre los 42 artefactos reales del pipeline de la Semana 16 | 0 hallazgos |
| Permisos y tenant de la API de ejecuciones (BN01…BN04, BN21…BN23) | En verde |
| Ensayo de la guía de despliegue | P2, P4, §2, V1, V2, V4 y rollback OK; P1, P3 y P5/P6 limitados por PostgreSQL; V3 con hallazgo |
| Despliegues de micro-HIS con release roto | Rollback automático en el expediente y en Gobernanza |

## Hallazgos

- **H-QA-17-01:** evidencias conservadas sin revisión de secretos → escáner nuevo con pruebas.
- **H-QA-17-02:** con CENTRAL caída, `GET /auth/me` sin token responde 500 (la guía espera 401). Es del middleware de
  Tenants; se avisa a Marielos y se ajusta la guía, sin tocar su código.
- Riesgos medios sin cambio: acciones de terceros sin fijar por SHA (Q-03) y etapas informativas (Q-09, B-04).

## Pendiente

Ejecutar la guía completa con PostgreSQL y HTTPS cuando exista el servidor del grupo y GitHub Actions vuelva a correr.
