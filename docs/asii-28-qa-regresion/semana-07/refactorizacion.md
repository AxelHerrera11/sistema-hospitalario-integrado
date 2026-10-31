# Refactorización aplicada y regresión

## Oportunidad real

Antes, CaseView::created usaba get_object_vars($case). La salida coincidía con
el contrato actual, pero toda nueva propiedad pública del dominio se publicaría
automáticamente. Es acoplamiento entre estado interno y representación, no un
defecto de datos confirmado ni una filtración reproducida en la versión actual.

Después, la vista proyecta explícitamente purpose, steps, expected e id. Conserva
orden, valores, status 201 y body.data; no introduce DTO, dependencia o regla nueva.
La lista explícita hace que ampliar ManualCase no amplíe implícitamente el JSON.
Los tres errores del controlador, el dominio y los adaptadores no se modifican.

| Antes | Después | Garantía preservada |
|---|---|---|
| data = get_object_vars($case) | data con cuatro claves explícitas | Misma forma y datos públicos actuales |
| Cobertura de alta por ambos adaptadores | Misma batería + caracterización de CaseView | Sustitución y salidas previas compatibles |

Archivos cambiados: src/Presentation/CaseView.php y tests/run.php dentro de
micro-his/qa-regresion. No se modifican documentos de Semanas 1–6 ni otros módulos.

## Evidencia nueva — 29/10/2026

Comando `php micro-his/qa-regresion/tests/run.php` ejecutado en tres momentos:

1. Antes de cambios: **17 aprobadas, 0 fallidas**.
2. Añadida prueba de caracterización, todavía con get_object_vars: **18 aprobadas, 0 fallidas**.
3. Proyección explícita aplicada: **18 aprobadas, 0 fallidas**.

La nueva prueba compara exactamente el sobre y las cuatro claves/valores, incluido
pasos multilínea. Es una caracterización de compatibilidad, no prueba de una fuga
actual. Las 17 originales mantienen happy path, reglas, ausencia, duplicados,
fallos de save/consulta y ambos adaptadores. El harness no informa aserciones ni
cobertura porcentual; no se inventan. SQLite es en memoria y sin datos reales.

```powershell
'{"purpose":"Revisar QA","steps":"Abrir formulario","expected":"Campos visibles"}' | php micro-his/qa-regresion/demo.php
# status 201; data con los cuatro campos, id nuevo de 32 caracteres.
'{"purpose":[]}' | php micro-his/qa-regresion/demo.php
# status 400, body.error INVALID_INPUT.
'{"purpose":"   ","steps":"Abrir","expected":"Visible"}' | php micro-his/qa-regresion/demo.php
# status 422, body.error CASE_INCOMPLETE.
'incorrecto' | php micro-his/qa-regresion/demo.php
# status 400, body.error INVALID_JSON.
php -l micro-his/qa-regresion/src/Presentation/CaseView.php
php -l micro-his/qa-regresion/tests/run.php
# Ambos sin errores de sintaxis.
```

Error de persistencia: la suite posterior conserva PASS doble fallo seguro,
PDO sin tabla fallo seguro y PDO fallo de consulta traducido. No se presenta
status CLI como HTTP ni se conecta a bases hospitalarias.

## Validaciones documentales y limitaciones

Se contrastaron responsabilidades y puertos con código real y con el PlantUML
de Semana 3. Los PR #1367/#1368 están fusionados; #1400/#1401 se consultan desde
sus ramas en modo lectura por seguir abiertos. No se copian sus commits.
PlantUML 1.2026.9 temporal: sintaxis y render SVG/PNG aprobados; imagen inspeccionada.
Grafo: 11 nodos/18 aristas sin ciclos; enlaces relativos existentes: PASS.
git diff --check y git diff --cached --check: PASS; diff completo dentro de 400 líneas.
No hay API HTTP ni cliente, campañas o defectos implementados. LSP probado se
limita al repositorio de casos; no demuestra el contrato futuro de ejecuciones.
Revisión técnica y aprobación siguen pendientes; Madelin Cerón mantiene evidencia.

Comandos del diagrama ejecutados (JAR/salidas fuera del repositorio):

```text
java -jar <TEMP>/asii28-plantuml-v1.2026.9.jar -checkonly docs/asii-28-qa-regresion/semana-07/diagrama-componentes.puml
java -jar <TEMP>/asii28-plantuml-v1.2026.9.jar -tsvg -o <TEMP>/render docs/asii-28-qa-regresion/semana-07/diagrama-componentes.puml
java -jar <TEMP>/asii28-plantuml-v1.2026.9.jar -tpng -o <TEMP>/render docs/asii-28-qa-regresion/semana-07/diagrama-componentes.puml
```

Sin errores; SVG 24834 bytes, PNG 77387 bytes. Solo se versiona fuente .puml.
