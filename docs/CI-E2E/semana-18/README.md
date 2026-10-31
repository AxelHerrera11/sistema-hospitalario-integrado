# Semana 18 — Evaluación final y defensa

**Módulo:** QA, pruebas E2E, CI y guía de despliegue final · **Adaptación:** gestión de ejecuciones automatizadas y
calidad de versiones.

**Tarea:** demostrar el proceso funcionando, comprobar que todo está integrado y reunir la evidencia completa.

## Entregables

| Archivo | Contenido |
|---|---|
| [`guion-demo.md`](guion-demo.md) | Demo de 10 minutos y preguntas probables |
| [`evidencia-completa.md`](evidencia-completa.md) | Semanas 1–18 con PR, estado final de RF/RNF, hallazgos y bloqueos |
| [`evidencias/01-demo.txt`](evidencias/01-demo.txt) | E2E del módulo contra su servidor y suites, sobre la versión integrada |
| [`evidencias/02-integracion.txt`](evidencias/02-integracion.txt) | Integración local de `develop` con los PR abiertos, pipeline completo y quality gate |

## PR integrado

28 PR del módulo están fusionados en `develop` (#812–#838 y #1485 de la Semana 16). El de la Semana 17 (#1509) está
abierto. Como prueba de integración final, se fusionaron localmente sobre `develop` los 7 PR abiertos de mis módulos
(#1503–#1507, #1509, #1510) **sin conflictos** y se ejecutó el pipeline completo con el quality gate sobre ese
resultado. Esa es la versión que se presenta.

## Resultado sobre la versión integrada (2026-10-29)

Ver [`02-integracion.txt`](evidencias/02-integracion.txt): etapas del pipeline, veredicto del gate con sus motivos,
clasificación de los fallos y escaneo de secretos de las evidencias.

## Qué se defiende

1. **Proceso completo:** planificar, ejecutar, conservar evidencia con huella y decidir con una política versionada.
2. **Gate que no se engaña:** sin evidencia no aprueba; una suite omitida o ausente rechaza o bloquea.
3. **Lectura del gate:** separar fallos de entorno (PostgreSQL) de fallos de código antes de culpar a un módulo.
4. **Despliegue reversible:** guía ensayada paso a paso y releases de micro-HIS con rollback automático.
5. **Evidencias seguras:** secretos efímeros enmascarados y escáner antes de conservar.

## Límites declarados

GitHub Actions no ejecuta jobs desde el 24-oct-2026 por la facturación de la organización (B-03); la suite
`Database`, el E2E de pila completa y la guía con PostgreSQL quedan para cuando exista el servidor del grupo.
