# PR de módulo ASII

## Resumen

Semana 15: nueve pruebas PHP nuevas, implementadas y ejecutadas sobre código existente.
Tres unitarias, dos de caja blanca y cuatro de caja negra, con aserciones estrictas,
evidencia y trazabilidad. Autora: Madelin Jazmín Cerón Molina; fecha: 30/10/2026.

## Módulo asignado

- ASII-28 — QA funcional, pruebas manuales y matriz de regresión.
- Estudiante: Madelin Jazmín Cerón Molina. GitHub: MadelinCeron. Refs #12.
- Rama: `feature/asii-28-semana-15-qa-regresion-madelinceron`; base del PR: `develop`.
- HEAD inicial/bajo prueba: `08796d859d330d3a76656318ff73599e05633af7`, limpio e igual a origin/develop tras fetch. Sin pull a ciegas ni commits pendientes incorporados.

## Alcance

Nuevo [runner independiente](../../../micro-his/qa-regresion/tests/semana15.php),
[matriz de nueve casos](./matriz-casos-prueba.md), [ejecución y evidencia](./ejecucion-evidencias.md)
y [trazabilidad/resultados SQA](./trazabilidad-resultados.md).
Mantiene el runner anterior intacto y no cambia src, demo, configuración, otros módulos
o documentación previa. Sin frameworks nuevos, worktrees, HTTP ni bases hospitalarias.
Real: dominio/aplicación/controlador CLI de casos. Campañas, ejecuciones, evidencia
privada y defectos backend siguen pendientes; HTML es una simulación separada.

### Fuentes y estado real

Se inspeccionaron todos los PHP de micro-his/qa-regresion, bootstrap, esquema SQLite,
runner existente, [RF/RNF reales](../semana-02/rf-rnf-criterios.md),
[contratos de Semana 4](../semana-04/README.md) y plantilla oficial del PR en la base.
GitHub confirmó abiertos/no fusionados #1400, #1401, #1402, #1403, #1408, #1452,
#1492, #1508, #1512 y #1516. Solo lectura mediante git show sobre ramas remotas:

| Fuente publicada / commit exacto | Documento consultado bajo docs/asii-28-qa-regresion |
|---|---|
| #1400 / `5de4b20cb021665bb56f08ab1582a08ace860a3c` | semana-05/contrato-api.md: REST propuesto, sin implementación HTTP QA |
| #1402 / `e131ecaa316432607c8baa01c075cd329794a563` | semana-07/refactorizacion.md: caracterización de 18 casos solo en rama pendiente |
| #1492 / `bd89ef5f85d81d2d867dad51e2f68911496331dc` | semana-11/README.md: prototipo y límites; no se reejecuta HTML en esta entrega |
| #1508 / `ad38f63390956862453db3730f27da665e1754fc` | semana-12/matriz-trazabilidad.md: alcance de trece requisitos |
| #1512 / `bf3b7f8f8f2aa683177446b4821f6ad858ecb3f9` | semana-13/hallazgos-tecnicos.md: ocho REV abiertos |
| #1516 / `d9956b0929a4f633a7164d38aa69f9c1f88de5f9` | semana-14/metricas-calidad.md: fórmulas/denominadores y límites SQA |

## Evidencia obligatoria

- [ ] Worktree: no utilizado por instrucción expresa.
- [x] Sin main, mezcla de módulos, secretos o datos clínicos; Refs #12.
- [x] Requisitos, arquitectura existente, casos nuevos, fallos controlados y riesgos delimitados.
- [x] Aserciones, ejecución, regresión, sintaxis, enlaces y diff verificados.

## Validación ejecutada

```text
php micro-his/qa-regresion/tests/semana15.php
php micro-his/qa-regresion/tests/run.php
php -l micro-his/qa-regresion/tests/semana15.php
```

Suite nueva: **9 aprobadas, 0 fallidas, 0 bloqueadas** (3 UT, 2 WB, 4 BB).
Regresión anterior: **17 aprobadas, 0 fallidas**; runner separado, no se reporta 26
como un conjunto único ni como cobertura global. Doce PHP pasan sintaxis.
Control negativo en copia TEMP: al omitir guarda de ManualCase, fallan UT-01/WB-02/BB-03
(6 aprobadas, 3 fallidas; salida 1 esperada). No son defectos del código original;
hash original verificado intacto. Detalles reproducibles en ejecución-evidencias.md.
Enlaces, nueve IDs, clasificación, trazabilidad y `git diff --check` comprobados;
`git diff --cached --check` sin errores antes del commit; diff completo ≤400 líneas.

## Contrato API si aplica

201/400/422/503 son resultados del controlador/CLI; no solicitudes HTTP.
POST/GET manual-cases de #1400 siguen propuestos. BB-04 inyecta un doble controlado,
sin modificar conexiones ni simular una infraestructura de autorización.

## Evidencia visual si aplica

No aplica a esta suite PHP. HTML no ejecutado en Semana 15; 17/17 de Semana 14 es
antecedente, no medición nueva ni parte de los nueve casos mínimos.

## Riesgos y pendientes

Sin porcentaje de cobertura de código ni RNF completos acreditados. RF-01 permanece
como único requisito verificado en backend CLI acotado; seguridad/tenant/persistencia
integrada y revisión independiente pendientes. Ningún REV anterior se cierra aquí.
Madelin responde por sus pruebas/documentos/evidencia. Revisor independiente y roles
de corrección futura: Pendiente de asignación. Terminar la serie académica no autoriza producción.

## Checklist final del estudiante

- [x] Un PR pequeño: cuatro documentos nuevos y un runner; código funcional previo intacto.
- [x] Nueve casos implementados/ejecutados, regresión y limitaciones documentadas.
- [ ] Revisión independiente y aprobación pendientes; no se fusiona ni se cierra #12.

Resumen de cambios: unidades aisladas, ramas de validación/excepción, contrato CLI y recuperación del controlador.
