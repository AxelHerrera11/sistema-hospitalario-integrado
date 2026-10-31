# Evidencia de validación — 28/10/2026

## Fuentes y comprobaciones reales

Se ejecutaron git status --short --branch, git branch --show-current,
git log -1 --oneline y git fetch origin. Rama correcta, limpia al inicio;
HEAD y origin/develop coinciden en 3ea30faa, sin cambios de base necesarios.
`git ls-tree --name-only origin/develop docs/asii-28-qa-regresion/semana-02/`
confirmó README.md, rf-rnf-criterios.md, solid-lsp.md y evidencia-verificable.md.
Se leyeron los cuatro; no se modificaron. API GitHub confirmó #144 fusionado
y #12 como issue de ASII-28. Se usa Refs #12, sin repetir su cierre histórico.

`rg --files micro-his` y búsquedas en app, micro-his y database/migrations de
ManualCase/RegressionCampaign/CampaignRepository/qa_campaign/qa_defect/manual_cases
no localizaron implementación específica del flujo ASII-28. Se contrastó también
la terminología de campañas/defectos y la fila ASII-28 del README raíz.
`micro-his/qa-calidad-versiones/README.md` identifica su propio alcance como
ejecuciones automatizadas/quality gates; no acredita campañas manuales ASII-28.
No se ejecutaron sus suites ajenas como prueba funcional de este módulo.

| Control arquitectónico | Resultado de inspección de la propuesta |
|---|---|
| Reglas sin SQL | Domain solo contiene conceptos/invariantes y puertos |
| Presentación sin consultas | Entrada/salida delega en casos de uso/contratos |
| Dependencias sin ciclos | Presentation → Application → Domain; Persistence → Domain; bootstrap compone |
| PDO preparado | Exigido al adaptador futuro; no hay implementación PDO ASII-28 que ejecutar |
| Contratos y LSP | Misma pre/postcondición para sustitutos; defecto separado de resultado aprobado |
| Trazabilidad | Ocho RF y cinco RNF enlazados a responsabilidades en arquitectura.md |
| Severidad y seguridad | Decisiones pendientes explícitas; sin enum ni permisos ficticios |

## Escenarios previstos: no ejecutados, sin PASS

| Caso | Preparación / acción futura | Resultado esperado | Estado |
|---|---|---|---|
| T01 Camino feliz | Caso diseñado y asociado; observación coincidente, comparación registrada y evidencia; registrar ejecución | Aprobado, relaciones/evidencia conservadas, nueva identidad | Pendiente de implementación |
| T02 Regla de dominio | Ejecución aprobada; intentar registrar defecto | Rechazo funcional común; no persiste defecto ni cambia ejecución | Pendiente de implementación |
| T03 Error de persistencia | Doble del repositorio falla al guardar ejecución/evidencias | Error común sin detalles PDO; sin éxito ni registro parcial | Pendiente de implementación |
| T04 Fallido y defecto | Observación no coincidente; registrar ejecución y luego defecto | Fallido; defecto enlaza ejecución → caso → campaña | Pendiente de implementación |
| T05 Historia | Registrar segunda ejecución del mismo caso | Primera ejecución, resultado y evidencia permanecen idénticos | Pendiente de implementación |
| T06 Sustitución LSP | Repetir T01–T05 con InMemory y PDO bajo igual contrato | Mismas garantías y rechazos, sin adaptar consumidor | Pendiente de ambos adaptadores |

No se afirma que el inicio del Micro-HIS o sus tres pruebas mínimas estén completos.
Son brechas de implementación para el siguiente incremento, no defectos reproducidos.
No se usaron datos clínicos ni bases existentes. La validación del diagrama y
la comprobación final se detallan a continuación.

## Validación final del entregable

PlantUML oficial 1.2026.9 descargado a TEMP, sin dependencia nueva en el repo.
Comandos (JAR y salida en carpeta temporal):

```text
java -jar <TEMP>/asii28-plantuml-v1.2026.9.jar -checkonly docs/asii-28-qa-regresion/semana-03/diagrama-arquitectura.puml
java -jar <TEMP>/asii28-plantuml-v1.2026.9.jar -tsvg -o <TEMP>/render docs/asii-28-qa-regresion/semana-03/diagrama-arquitectura.puml
java -jar <TEMP>/asii28-plantuml-v1.2026.9.jar -tpng -o <TEMP>/render docs/asii-28-qa-regresion/semana-03/diagrama-arquitectura.puml
```

Los tres comandos completaron sin error; SVG generado (27607 bytes) y PNG
inspeccionado visualmente: actores, cuatro capas y leyenda legibles.
Comprobación local de enlaces Markdown: PASS. Ordenación topológica de las
flechas del diagrama: PASS, 15 nodos y 20 aristas sin ciclos.
`git diff --check`, `git diff --cached --check` y revisión del diff completo
contra origin/develop: sin errores; solo cuatro archivos propios de Semana 3.
No se ejecutaron pruebas funcionales inexistentes ni se declara aprobación docente.
