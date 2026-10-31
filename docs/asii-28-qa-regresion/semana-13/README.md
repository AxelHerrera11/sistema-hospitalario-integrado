# PR de módulo ASII

## Resumen

ASII-28 — Semana 13: revisión técnica estructurada del núcleo disponible y diseños
publicados, elaborada el 29/10/2026. Es autorrevisión sustentada, no aprobación formal independiente.

## Módulo asignado

- Estudiante/autora: Madelin Jazmín Cerón Molina; GitHub: MadelinCeron. Refs #12.
- Módulo: ASII-28 — QA funcional, pruebas manuales y matriz de regresión.
- Rama: `feature/asii-28-semana-13-qa-regresion-madelinceron`; base del PR: `develop`.
- HEAD inicial y base revisada: `166544731741ec8e7af2198ceba681f80ae6cdaa`; árbol limpio y origin/develop igual tras fetch. Sin pull a ciegas.

## Alcance

Incluido: [15 criterios](./checklist-revision-tecnica.md), [ocho hallazgos](./hallazgos-tecnicos.md)
y [plan priorizado](./plan-acciones-correctivas.md), con responsables y verificadores diferenciados.
Fuera de alcance: cambios PHP/HTML, semanas anteriores, integración de PR abiertos,
infraestructura nueva, pruebas ofensivas y bases hospitalarias. No se usa worktree.
Método: inspección de código/contratos, contraste RF/RNF, pruebas existentes y revisión documental UX.

### Fuentes exactas revisadas

Las rutas documentales siguientes parten de `docs/asii-28-qa-regresion/`.
Consulta de PR mediante GitHub y documentos mediante `git show origin/<rama>:<ruta>`;
ramas con patrón `feature/asii-28-semana-NN-qa-regresion-madelinceron`.
Todos los ocho PR listados están **abiertos/no fusionados**, comprobado en esta revisión.

| Fuente / versión Git revisada | Archivos inspeccionados y alcance |
|---|---|
| Base `166544731741ec8e7af2198ceba681f80ae6cdaa` | semana-02/rf-rnf-criterios.md; semana-03/arquitectura.md; todos los once PHP y database/schema.sqlite.sql de micro-his/qa-regresion; routes (búsqueda QA); plantilla oficial del PR |
| #1400 / `5de4b20cb021665bb56f08ab1582a08ace860a3c` | semana-05/contrato-api.md: REST preliminar |
| #1401 / `a5773dcd0278991f994fefb34e349a0ac74569c6` | semana-06/README.md: alcance y resultados históricos |
| #1402 / `e131ecaa316432607c8baa01c075cd329794a563` | semana-07/componentes.md, refactorizacion.md: proyección explícita aún no integrada |
| #1403 / `508deb1b276d574cec5c6f179b3f067d875b8572` | semana-08/reglas-interaccion.md: flujo/WF y contratos UX documentales |
| #1408 / `3161aa928478678b69d1fade23ad6264d7c47ef8` | semana-09/hallazgos.md: UX-01–06; evidencia documental, no nueva prueba de wireframes |
| #1452 / `63f00ed0a2295d69234d7ce4b8637adc351fa8c7` | semana-10/diseno-responsive.md: decisiones y mediciones históricas |
| #1492 / `bd89ef5f85d81d2d867dad51e2f68911496331dc` | semana-11/prototipo-navegable.html y verificar-prototipo.mjs: lectura y nueva ejecución aislada |
| #1508 / `ad38f63390956862453db3730f27da665e1754fc` | semana-12/matriz-trazabilidad.md, guion-defensa.md, presentacion.md: contraste de alcance y trece requisitos |

### Participantes y responsabilidades

| Rol | Responsable / estado |
|---|---|
| Autoría, inspección, ejecución de pruebas y corrección documental de esta entrega | Madelin Jazmín Cerón Molina |
| Moderación y revisión técnica independiente | Pendiente de asignación; no se afirma sesión formal celebrada |
| Correcciones backend/frontend/seguridad futuras | Roles propuestos, Pendiente de asignación; detalle en plan |
| Verificación independiente del cierre y aprobación | Revisor técnico del módulo, Pendiente de asignación; distinto de autoría/corrección |

## Evidencia obligatoria

- [ ] Worktree: no utilizado por instrucción del encargo.
- [x] Sin main, otros módulos o incorporación de commits pendientes; Refs #12.
- [x] Arquitectura, contratos, RF/RNF, evidencias UX y riesgos de seguridad/tenant revisados.
- [x] Pruebas ejecutadas nuevamente y limitaciones identificadas; sin datos sensibles reales.

## Validación ejecutada

Ambiente Windows, PHP 8.2.12, PDO SQLite en memoria, Node 24.15.0 y Edge 156.0.4314.50.

| Comando / comprobación nueva | Resultado real |
|---|---|
| `php micro-his/qa-regresion/tests/run.php` | 17 aprobadas, 0 fallidas; casos/ambos adaptadores, no ejecución QA completa |
| `php -l <archivo>` para cada PHP del módulo | Once archivos sin errores de sintaxis |
| `'{"purpose":"Caso ficticio S13","steps":"1. Abrir formulario","expected":"Campos visibles"}'` por STDIN a `php micro-his/qa-regresion/demo.php` | 201 con cuatro campos/ID; repetir con purpose vacío → 422 CASE_INCOMPLETE |
| `node --check <TEMP>/verificar-prototipo.mjs`; ejecución descrita abajo | Sintaxis válida; 17 comprobaciones HTML aprobadas, 0 fallidas |
| Búsqueda QA en routes (comando exacto abajo) | Sin coincidencias, salida 1 esperada; más inspección del núcleo confirma ausencia de adaptador QA HTTP |
| Conteos/enlaces/fuentes Git y diff | Quince RT, trece RF/RNF y correspondencia REV→acciones comprobados; `git diff --check` y `git diff --cached --check` sin errores antes del commit |

```text
rg -n 'qa/manual|qa-regresion|ManualCase' routes
```

Prototipo extraído a TEMP desde #1492 con git show, UTF-8 sin BOM; blobs idénticos:
HTML `2bf0afaeb4cc39e4d08ac8fde2f39e6356b6d9e1`, script `3498fcb7dc098a546258c7ce94f315a742084074`.
Perfil Edge temporal, iniciado con `--headless --disable-gpu --no-first-run --remote-debugging-port=9341 --user-data-dir=<TEMP>/perfil about:blank`.
Comando: `node <TEMP>/verificar-prototipo.mjs http://127.0.0.1:9341 <TEMP>/capturas`.
Proceso aislado cerrado al terminar; no se modificaron archivos publicados.
Resultados: seis vistas/una visible, cero IDs duplicados, controles sin label o destinos internos rotos;
Tab/Enter termina en #campaigns con foco campaigns-title y contorno 3 px; errores, diálogo,
registro simulado, bloqueo desde aprobado y reinicio al recargar comprobados; cero excepciones JS.
Viewport/scrollWidth: 1280/1280, 390/375, 320/305, sin desborde horizontal.
No se suman las dos suites como cobertura integral; no se reejecuta la prueba extra pendiente de #1402.

## Contrato API si aplica

POST `/api/v1/qa/manual-cases` y GET `/api/v1/qa/manual-cases/{id}` son propuestas de #1400.
No hay rutas QA HTTP operativas ni permisos QA registrados por este núcleo. Los status son datos CLI.

## Evidencia visual si aplica

No se añaden capturas al PR; la prueba genera cuatro PNG solo temporales.
Las [cuatro capturas publicadas de Semana 11](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/tree/bd89ef5f85d81d2d867dad51e2f68911496331dc/docs/asii-28-qa-regresion/semana-11/capturas)
son antecedentes del mismo prototipo, no evidencia de producción ni una auditoría asistiva nueva.

## Riesgos y pendientes

Resultado: **5 Conforme, 8 Parcial, 2 No conforme, 0 No verificable, 0 No aplica**.
No verificable se registra también como límite específico dentro de criterios parciales (p. ej. lector de pantalla).
Ocho hallazgos: **3 Alta, 4 Media, 1 Baja; 0 Crítica**, todos abiertos.
No conforme en seguridad/tenant significa insuficiencia frente a integración multiusuario,
no explotación demostrada de una demo local. No se certifica aislamiento ni WCAG.
Semana 14: seguir prioridades P1 seguridad/tenant/dominio, luego P2 API/persistencia/
errores/accesibilidad y P3 serialización. Decisiones y correcciones no están completadas.

## Checklist final del estudiante

- [x] Solo cuatro documentos propios de Semana 13; PR menor de 401 líneas.
- [x] Evidencia y límites explícitos; sin secretos, cambios globales ni bases hospitalarias.
- [ ] Revisión independiente y aprobación pendientes; sin evaluación ficticia de terceros.

Resumen de cambios: revisión de arquitectura, contratos, pruebas, brechas y acciones con cierre verificable.
