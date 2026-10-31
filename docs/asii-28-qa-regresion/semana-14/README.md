# PR de módulo ASII

## Resumen

Plan SQA de ASII-28: prevenir desviaciones, medir calidad y exigir evidencia antes de
integrar campañas, ejecuciones y defectos. Elaborado el 29/10/2026; metas y liberación
son propuestas sujetas a revisión, no certificación ni autorización productiva.

## Módulo asignado

- Estudiante: Madelin Jazmín Cerón Molina; GitHub: MadelinCeron. Refs #12.
- ASII-28 — QA funcional, pruebas manuales y matriz de regresión.
- Rama: `feature/asii-28-semana-14-qa-regresion-madelinceron`; destino: `develop`.
- HEAD inicial: `166544731741ec8e7af2198ceba681f80ae6cdaa`; limpio e igual a origin/develop tras fetch. Sin pull, worktrees ni incorporación de PR pendientes.

## Alcance

- [Plan SQA](./plan-sqa.md): roles, prácticas, pruebas, gestión y puertas de calidad.
- [Siete métricas](./metricas-calidad.md): fórmulas, línea base, metas y desviaciones.
- [Riesgos, controles y trazabilidad](./matriz-riesgos-controles.md): ocho riesgos y trece RF/RNF.

Backend real: CaseController → DesignCase → ManualCase/CaseRepository → InMemory/PDO;
solo RF-01, PHP vanilla y almacenamiento de demo en memoria. Contratos REST son
propuestas. HTML navega y registra simulaciones locales, sin API y pierde estado al recargar.
Solo cuatro documentos de Semana 14; sin cambios de código, configuración, CI o PostgreSQL.

### Fuentes y método

Requisitos de [Semana 2](../semana-02/rf-rnf-criterios.md), [arquitectura](../semana-03/arquitectura.md)
y [núcleo/contratos de Semana 4](../semana-04/README.md), desde base 16654473.
Se leyeron pruebas PHP, Controller, PDO, esquema y demo; contratos/diseños remotos mediante
`git show origin/feature/asii-28-semana-NN-qa-regresion-madelinceron:<ruta>`.
Rutas de la tabla relativas a docs/asii-28-qa-regresion; estados consultados en GitHub ahora:

| PR / semana / commit fuente | Material consultado | Estado |
|---|---|---|
| #1400 / 05 / `5de4b20cb021665bb56f08ab1582a08ace860a3c` | contrato-api.md | Abierto, no fusionado |
| #1401 / 06 / `a5773dcd0278991f994fefb34e349a0ac74569c6` | Estado del PR; no se reejecuta su defensa histórica | Abierto, no fusionado |
| #1402 / 07 / `e131ecaa316432607c8baa01c075cd329794a563` | componentes.md; proyección explícita aún pendiente de integración | Abierto, no fusionado |
| #1403 / 08 / `508deb1b276d574cec5c6f179b3f067d875b8572` | reglas-interaccion.md: flujo y wireframes documentados | Abierto, no fusionado |
| #1408 / 09 / `3161aa928478678b69d1fade23ad6264d7c47ef8` | hallazgos.md: UX-01–06 | Abierto, no fusionado |
| #1452 / 10 / `63f00ed0a2295d69234d7ce4b8637adc351fa8c7` | README.md: diseño responsive y límites históricos | Abierto, no fusionado |
| #1492 / 11 / `bd89ef5f85d81d2d867dad51e2f68911496331dc` | prototipo-navegable.html y verificar-prototipo.mjs | Abierto, no fusionado |
| #1508 / 12 / `ad38f63390956862453db3730f27da665e1754fc` | matriz-trazabilidad.md | Abierto, no fusionado |
| #1512 / 13 / `bf3b7f8f8f2aa683177446b4821f6ad858ecb3f9` | README, checklist-revision-tecnica, hallazgos-tecnicos y plan-acciones-correctivas (.md) | Abierto, no fusionado |

Semana 13 verificada documentalmente: 15 criterios (5 conformes, 8 parciales,
2 no conformes), ocho REV abiertos (3 Alta, 4 Media, 1 Baja), ocho AC pendientes.
Son antecedentes fijados, no una nueva revisión de quince criterios ni acciones cerradas.

## Evidencia obligatoria

- [ ] Worktree: no utilizado por instrucción expresa.
- [x] Sin main ni otros módulos; Refs #12; RF/RNF, arquitectura, roles y riesgos delimitados.
- [x] Evidencias y pruebas nuevas registradas; sin credenciales o datos clínicos reales.

## Validación ejecutada

Windows; PHP 8.2.12, SQLite en memoria; Node 24.15.0, Edge 156.0.4314.50 aislado.
`php micro-his/qa-regresion/tests/run.php`: **17 aprobadas, 0 fallidas**.
Demo CLI por STDIN: purpose=“Caso ficticio S14”, steps=“1. Abrir formulario”, expected=“Campos visibles”:
201 con cuatro campos/ID; repetir con purpose vacío: 422 CASE_INCOMPLETE. No son respuestas HTTP.
Se extrajo HTML/script de #1492 a TEMP; git hash-object coincide con la fuente:
HTML `2bf0afaeb4cc39e4d08ac8fde2f39e6356b6d9e1`, script `3498fcb7dc098a546258c7ce94f315a742084074`.

```text
msedge.exe --headless --disable-gpu --no-first-run --remote-debugging-port=9341 --user-data-dir=<TEMP>/perfil about:blank
node --check <TEMP>/verificar-prototipo.mjs
node <TEMP>/verificar-prototipo.mjs http://127.0.0.1:9341 <TEMP>/capturas
```

Sintaxis válida, **17 comprobaciones HTML aprobadas, 0 fallidas**, seis vistas/una visible,
cero IDs duplicados, controles sin etiqueta, enlaces internos rotos o excepciones JS observadas.
Tab/Enter: #campaigns, foco campaigns-title, outline 3 px; errores, diálogo, guardas y reset comprobados.
Viewport/scrollWidth: 1280/1280, 390/375, 320/305, sin desborde horizontal; perfil cerrado al terminar.
Conteos, aritmética, vínculos RF/RNF–riesgo–control, enlaces y referencias Git comprobados;
`git diff --check` y `git diff --cached --check` sin errores antes del commit; diff completo menor de 401 líneas.

## Contrato API si aplica

#1400 propone POST `/api/v1/qa/manual-cases` y GET `/api/v1/qa/manual-cases/{id}`.
No hay adaptador QA HTTP implementado ni integración HTML/PHP; permisos y tenant pendientes.

## Evidencia visual si aplica

Prueba CDP genera cuatro PNG solo temporales; no se agregan capturas al PR.
[Capturas publicadas de Semana 11](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/tree/bd89ef5f85d81d2d867dad51e2f68911496331dc/docs/asii-28-qa-regresion/semana-11/capturas)
son antecedentes, no capturas de producción ni certificación de accesibilidad.

## Riesgos y pendientes

Sin cobertura porcentual de código verificable, participantes, lector de pantalla o zoom real.
Seguridad/tenant, API/frontend y persistencia integrada pendientes; pasar pruebas no habilita liberación.
Madelin mantiene sus documentos/evidencia; desarrollo, seguridad, pruebas futuras y revisión independiente:
**Pendiente de asignación**. Semana 15 debe preparar al menos 3 unitarias, 2 caja blanca y
4 caja negra con registros separados; esos mínimos no se declaran ejecutados aquí.

## Checklist final del estudiante

- [x] Cuatro documentos propios, siete métricas, ocho riesgos y trece RF/RNF, sin cambios funcionales.
- [x] Límites/mediciones explícitos y sin secretos; PR pequeño y revisable.
- [ ] Revisión independiente y aprobación del plan pendientes.

Resumen de cambios: plan preventivo, línea base medible, controles y seguimiento sin cierres ficticios.
