# Evidencia — Semana 5: persistencia clínica

## Ejecución histórica del 23 de octubre de 2026

PHP 8.2.12 y PostgreSQL 16, HOSPITAL de pruebas en 127.0.0.1:55433. Estos resultados corresponden al árbol anterior a la reescritura de la pila; no acreditan el head c53aa59d de #692 ni el SHA actual de #1315. Se conservan como registro histórico.

- SpecimenIntegrityTest: 5 pruebas, 74 aserciones.
- Micro independiente: run.php, 39 comprobaciones; lsp.php, 6 comprobaciones con 2 adaptadores; postgres.php, 13 comprobaciones.
- Pint sobre micro, adaptador, migración y SpecimenIntegrityTest: aprobado en esa ejecución.
- Grupo database-integrity: 130 pruebas, 1466 aserciones, 678.70 s en ese árbol histórico.

Los pasos numéricos de rollback de esa versión quedaron reemplazados por hospitalRollbackSteps($desde). No deben copiarse a la versión integrada. El helper cuenta las migraciones posteriores hasta el prefijo objetivo inclusive; véase [README](README.md).

## Verificación reportada por la revisora sobre la versión integrada

La [revisión de Yamilet1235 en #1315](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1315) informa 105 pruebas y 1142 aserciones de integridad y rollback con PostgreSQL 17.10 para el contexto de #692 integrado (c53aa59d, merge adb128d3). Es evidencia reportada por la revisora; no una ejecución nueva realizada por Codex. La revisión se identifica también por su fecha UTC 2026-10-28 04:06:46 y head revisado 9c50ab57 de #1315.

## Alcance de esta corrección documental

Se contrastaron rutas, namespaces, migración y cálculo de rollback con develop adb128d3. #1315 solo modifica documentación y diagramas; no se reejecutaron PostgreSQL ni la suite HTTP para esta corrección. Las rutas llegan con #693 y la proyección de órdenes con #694. No se afirma CI remoto aprobado.
