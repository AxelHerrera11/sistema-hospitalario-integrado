# ASII-28 - QA Funcional - Semana 7 - Diseño de componentes y refactorización

Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Rama: `feature/asii-28-semana-07-qa-regresion-madelinceron`; base `develop`.
HEAD inicial: `52097752bd37ea40b37f986259fc4c0aec0da2c6`, igual a origin/develop
tras fetch; árbol inicialmente limpio, sin pull a ciegas ni worktrees.

## Objetivo y alcance

Modelar componentes y contratos del Micro-HIS existente y aplicar una refactorización
acotada: CaseView selecciona explícitamente los campos públicos del caso, conservando
el sobre JSON y el orden actual. No se implementa un flujo nuevo de QA.

- [Componentes y contratos](./componentes.md).
- [Diagrama UML editable](./diagrama-componentes.puml).
- [Comparación antes/después y evidencia](./refactorizacion.md).

Se revisaron [análisis](../semana-01/README.md),
[RF/RNF y LSP](../semana-02/README.md),
[arquitectura y PlantUML](../semana-03/README.md),
[capas y Repository](../semana-04/README.md) y código de micro-his/qa-regresion.
#1367/#1368 están fusionados; #1400/#1401 siguen abiertos/no fusionados.
El [contrato de Semana 5](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1400)
y la [defensa de Semana 6](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1401)
se consultan desde sus ramas sin incorporar commits ni modificar documentos previos.

## Validaciones y límites

Nueva ejecución del 29/10/2026: suite original **17/17**; con caracterización del
JSON **18/18 antes** y **18/18 después** de cambiar CaseView. Demo CLI: 201 válido,
400 formato/JSON inválido y 422 regla de dominio. La suite conserva los fallos
inducidos de persistencia y la misma batería para InMemory/PDO.
Detalles de comandos, diagrama y comprobaciones en refactorizacion.md.

Solo está implementado diseñar/guardar un caso (RF-01), no campañas, ejecuciones,
evidencias o defectos. Los códigos son resultados CLI, no respuestas HTTP.
SQLite en memoria no acredita almacenamiento durable, tenant ni PostgreSQL.
Semana 8: revisar separación de componentes y preparar diseño de interfaz sobre
contratos aprobados, sin exponer rutas hasta acordar propiedad/seguridad QA.
Madelin Cerón responde por refactor/evidencia; revisión y aprobación pendientes.
