# Checklist técnico — 15 criterios

Versión, archivos exactos, método y comandos: [README](./README.md).
P = `micro-his/qa-regresion/` en la base `16654473`; SNN = documento de Semana NN
con commit fijado en el manifiesto. Los nombres de clase resuelven a P/src/<capa>/<clase>.php.
Conforme se restringe al aspecto/alcance indicado, no al módulo completo.
No conforme evalúa preparación para integración, sin declarar vulnerabilidad crítica.
Responsables: MC = Madelin Cerón, documental; B = backend QA; F = frontend QA;
S = seguridad; T = pruebas. B/F/S/T son roles propuestos, **Pendiente de asignación**.
Verificación independiente de todos los cierres: revisor técnico, **Pendiente de asignación**.

| ID / aspecto | Método y evidencia inspeccionada | Resultado observado | Hallazgo / riesgo | Responsable / acción correctiva | Estado |
|---|---|---|---|---|---|
| RT-01 Organización | Inventario P/bootstrap.php, src, database y tests; docs Semanas 2–4 | Núcleo separado, autoload propio, demo/tests aislados y alcance identificable | Sin hallazgo en organización | MC: mantener inventario/versiones | Conforme |
| RT-02 Capas | Lectura de Controller, DesignCase, ManualCase, CaseRepository y adaptadores; S03 arquitectura.md | Application recibe puerto Domain; Domain no importa infraestructura; SQL en Persistence | Sin hallazgo para RF-01 | MC: conservar fronteras al documentar incrementos | Conforme |
| RT-03 Componentes | P/Presentation/CaseView::created (ruta completa P/src/Presentation/CaseView.php); S07 componentes/refactorizacion | Controller adapta, caso de uso coordina; vista usa get_object_vars en base | REV-05 / Baja: representación acoplada a propiedades públicas | B: revisar proyección explícita pendiente, AC-05 | Parcial |
| RT-04 Repository/LSP | CaseRepository::save/find, InMemory/PDO; suite común; S02 RNF-04 | Sustitución de casos probada, no registro de ejecuciones | REV-03 / Alta: contrato de ejecuciones ausente | B/T: contrato y batería real de ejecuciones, AC-03 | Parcial |
| RT-05 Entradas | CaseController::create, ManualCase::__construct; suite y demos nuevas | Forma/tipo → 400; texto vacío → 422; tres textos completos aceptados | Sin hallazgo para contrato CLI; límites HTTP adicionales son propuesta | MC: preservar evidencia acotada | Conforme |
| RT-06 Dominio | ManualCase readonly/trim; inventario Domain y S11 handlers JS | RF-01 aplicado en dominio; resultado/defecto solo simulados en HTML | REV-03 / Alta: faltan reglas backend RF-02–08 | B/T: invariantes y escenarios coherentes, AC-03 | Parcial |
| RT-07 Errores | Controller captura DomainException/StorageFailure; PDO traduce; tests de fallo; demo.php | 400/422/503 acotados; conexión/esquema arrancan antes del try | REV-06 / Media: fallos de arranque no normalizados, riesgo por inspección | B: frontera CLI segura y pruebas de arranque, AC-06 | Parcial |
| RT-08 API | S05 contrato-api.md, lectura núcleo y búsqueda en routes | Contrato distingue propuesta; no adaptador HTTP/consulta Application | REV-04 / Media: integración REST pendiente | B/S: validar contrato y seguridad antes de exponer, AC-04 | Parcial |
| RT-09 Persistencia | P/database/schema.sqlite.sql; save/find; demo/tests SQLite::memory | SQL parametrizado, duplicados no sobrescriben; no durabilidad entre procesos demostrada | REV-07 / Media: demo pierde datos y no persiste grafo QA | B/T: estrategia aislada/durable y recuperación, AC-07 | Parcial |
| RT-10 Seguridad/autorización | Inventario completo P; Controller/DesignCase/schema; S05 permisos propuestos | No autentica ni autoriza por sí mismo; no hereda middleware del HIS | REV-02 / Alta: falta frontera de acceso para integración | S/B: política/controles y pruebas, AC-02 | No conforme |
| RT-11 Tenant | Schema sin tenant/owner, ManualCase sin ámbito y find(id) sin filtro | No existe separación por tenant propia del módulo | REV-01 / Alta: datos sin propiedad de hospital para futuro uso compartido | S/B: decidir propiedad y aislamiento, AC-01 | No conforme |
| RT-12 Pruebas | P/tests/run.php y S11/verificar-prototipo.mjs ejecutados nuevamente | 17/17 PHP y 17/17 HTML; pruebas distintas, sin porcentaje global | REV-03 / Alta: no hay suite backend de ejecuciones/seguridad/historia | T/B: ampliar al implementar contratos, AC-03 | Parcial |
| RT-13 Trazabilidad | S02 ocho RF/cinco RNF, S12 matriz/guion/presentación; tabla inferior | Identificadores y distinción real/simulado/propuesto consistentes | Sin hallazgo documental; trazabilidad no equivale a aceptación aprobada | MC: mantener mapa y fuentes fijadas | Conforme |
| RT-14 UX/accesibilidad | S09 hallazgos, S10 evidencia documental y S11 nueva prueba CDP | Teclado/foco/error/reflow acotados; UX-01–03 atendidos localmente | REV-08 / Media: lector/zoom/tareas no verificados; UX-04–06 parciales/pendientes | F/T: protocolo autorizado y evidencia asistiva, AC-08 | Parcial |
| RT-15 Mantenibilidad | Lectura README base/contratos, manifiesto de versiones, enlaces y plantilla | Instrucciones CLI y fuentes identificables; PR abiertos separados de integración | Sin hallazgo para documentación revisada | MC: mantener límites, responsables y estado de PR | Conforme |

## RF/RNF: trazabilidad de revisión, no cobertura total

Fuente: [criterios exactos de Semana 2](../semana-02/rf-rnf-criterios.md).
I = implementado/verificado en alcance indicado; S = simulado parcial; D = documentado; P = pendiente.

| ID | Criterio sintetizado / RT relacionado | Evidencia disponible y estado real |
|---|---|---|
| RF-01 | Caso con propósito/pasos/esperado, rechazo incompleto; RT-05/06/09/12 | ManualCase/DesignCase y PHP 17/17; I CLI durante proceso, S HTML |
| RF-02 | Campaña conserva casos asociados; RT-06/09/13 | S03/S12 D, S11 asociación a campaña fija S; backend P |
| RF-03 | Ejecución vinculada y observaciones/comparación; RT-06/12 | S11 contexto/error/registro S; Application/Domain P |
| RF-04 | Resultado corresponde a comparación; RT-06/12 | S11 ambos resultados declarados S; coherencia semántica backend P |
| RF-05 | Evidencia pertenece a ejecución y se consulta; RT-09/10/13 | Diálogo/referencia ficticia S; integridad/acceso reales P |
| RF-06 | Defecto solo desde fallo y vinculado al origen; RT-06/12 | S11 confirmación y bloqueo desde aprobado S; dominio P |
| RF-07 | Consultar grafo e historial; RT-09/13 | S11 resumen de última ejecución S, consulta íntegra P; S12 D |
| RF-08 | Nueva ejecución conserva anteriores; RT-06/09/12 | Arreglos JS S; suite no compara todo dato previo; historial durable P |
| RNF-01 | Vínculos navegables defecto→ejecución→caso/campaña/evidencia; RT-09/11/13 | Confirmación/contexto S; integridad persistente P |
| RNF-02 | Comparación coherente, sin observaciones/esperado no guardar; RT-06/12 | Error incompleto S; coherencia semántica P |
| RNF-03 | Datos previos idénticos tras segunda ejecución; RT-09/12 | D en S02/S12; sin prueba completa de igualdad histórica, P |
| RNF-04 | Misma batería para registros de ejecución sustituibles; RT-04/12 | D/P; pruebas de CaseRepository no satisfacen este criterio de ejecución |
| RNF-05 | Un único aprobado/fallido por ejecución terminada; RT-06/12/14 | Selector/confirmaciones S; invariante backend P |

Balance: 5 Conforme, 8 Parcial, 2 No conforme; 0 No verificable y 0 No aplica globales.
Capacidades asistivas, zoom real y exposición en producción no verificables con esta sesión.
