# ASII-28 - QA Funcional - Semana 6 - Primera evaluación parcial

Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Rama: `feature/asii-28-semana-06-qa-regresion-madelinceron`; destino `develop`.
HEAD inicial: `52097752bd37ea40b37f986259fc4c0aec0da2c6`, igual a origin/develop
tras fetch; árbol limpio al iniciar. Sin worktrees ni incorporación de commits pendientes.

## Entrega y visualización

Defensa del alcance real: diseño y almacenamiento de casos manuales (RF-01),
cuatro capas, MVC, Repository y adaptadores InMemory/PDO. Se diferencia del
flujo completo propuesto de campañas, ejecuciones, evidencia y defectos.

- [Presentación: ocho diapositivas](./presentacion.md).
- [Guion y preguntas del docente](./guion-defensa.md).
- [Evidencia nueva y comandos reproducibles](./evidencia-evaluacion.md).

La presentación es Markdown editable, no PowerPoint. Abrir presentacion.md en
VS Code y usar **Ctrl+Shift+V**; ocho encabezados numerados y siete separadores
delimitan las diapositivas. La vista previa es continua, sin visor de slides externo.

## Fuentes y estado de integración

Se revisaron [Semana 1](../semana-01/README.md),
[requisitos/LSP de Semana 2](../semana-02/README.md),
[arquitectura de Semana 3](../semana-03/README.md) y
[núcleo de Semana 4](../semana-04/README.md). GitHub confirmó #144, #1367 y
#1368 fusionados y sus archivos están en la base. #1400 sigue abierto/no fusionado;
se consultó su [contrato propuesto](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/5de4b20cb021665bb56f08ab1582a08ace860a3c/docs/asii-28-qa-regresion/semana-05/contrato-api.md)
desde su rama sin copiar documentación o commits. Su aprobación no se presume.

## Demostración y resultados

Nueva ejecución del 29/10/2026, PHP 8.2.12: suite existente **17 aprobadas,
0 fallidas**. Demos: 201 caso válido; 400 INVALID_INPUT; 422 CASE_INCOMPLETE;
400 INVALID_JSON; 503 CASE_STORAGE_UNAVAILABLE con PDO sin tabla. Son resultados
CLI, no respuestas HTTP. SQLite en memoria y datos ficticios; sin .env ni PostgreSQL.
No se añadieron tests ni funcionalidades solo para la evaluación.

Pendientes para Semana 7: revisar contrato/propiedad tenant y permisos antes de
exponer HTTP, preparar cliente/componente de consulta y pruebas de seguridad
solo tras autorización del diseño. No hay UI QA, API HTTP, almacén durable,
campañas o defectos completos ni cobertura de todos los RF/RNF.
Madelin Cerón prepara defensa/evidencia; revisión, aprobación y evaluación docente
pendientes de asignación/realización. No se acredita una defensa oral ya realizada.
