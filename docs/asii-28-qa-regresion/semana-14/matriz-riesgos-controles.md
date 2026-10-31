# Riesgos, controles y trazabilidad

Fuentes fijadas en [README](./README.md); hallazgos originales REV/acciones AC de #1512
permanecen abiertos/pendientes. No se inventan nuevos defectos ni explotación confirmada.
Evaluación cualitativa **propuesta para futura integración**, no frecuencia estadística:
P: 1 baja posibilidad, 2 posible, 3 probable dadas brechas actuales; I: 1 menor/reversible,
2 afecta tarea/integración, 3 compromete acceso/integridad/historia. Nivel=P×I:
1–2 Bajo, 3–4 Medio, 6–9 Alto. Revisar primero Alto y controles de autorización/tenant.
B=backend, F=frontend, T=pruebas, S=seguridad; roles propuestos **Pendiente de asignación**.
Madelin mantiene sus documentos/evidencia; verificador independiente **Pendiente de asignación**.

| Riesgo / origen | Descripción | P/I/nivel | Prevención / corrección propuesta | Responsable | Evidencia de seguimiento / estado |
|---|---|---|---|---|---|
| R01 / REV-02 | Exponer futura API sin autorización propia conectada | 3/3/9 Alto | C02 revisar permisos/host; AC-02 implementar y verificar denegaciones | S/B | Política y tests identidad/permiso; abiertos, no implementados |
| R02 / REV-01 | Mezclar ámbitos al usar almacenamiento compartido sin tenant | 3/3/9 Alto | C02 acordar propiedad; AC-01 contexto obligatorio y pruebas cruzadas | S/B/T | Modelo/diff/resultados A/B; abierto, sin aislamiento acreditado |
| R03 / REV-03 | Registrar incoherencias o defectos desde origen inválido en futuro backend | 3/3/9 Alto | C01 invariantes en Domain; AC-03 implementación incremental y contrato común | B/T | Pruebas vínculos/comparación/historia/LSP de ejecución; abierto |
| R04 / REV-07 | Perder historia/evidencias al reiniciar almacenamiento de demo | 3/3/9 Alto | C03 política de persistencia aislada/retención; AC-07 recuperación entre procesos | B/T | Diseño y tests durables; abierto; demo efímera confirmada |
| R05 / REV-04 | Conectar UI a contrato HTTP sin implementación compatible | 2/2/4 Medio | C01 revisar contrato; AC-04 adaptador y tests tras AC-01/02 | B/S/T | Rutas/status/contrato y pruebas HTTP; abierto, propuesta documental |
| R06 / REV-08 | Barreras asistivas o recuperación de tarea no detectadas | 2/2/4 Medio | C04 criterios UX/protocolo; AC-08 pruebas lector/zoom/tareas y correcciones | F/T | Herramienta/navegador/tarea y observado; abierto, solo validación técnica parcial |
| R07 / revisión independiente pendiente en S13 | Integrar o cerrar acciones sin dictamen independiente | 3/2/6 Alto | C05 asignar revisor; C07 exigir evidencia y reabrir si falla | Revisor/T | Asignación y dictamen; abierto, sin atribuir aprobación a terceros |
| R08 / REV-03, REV-05, REV-06 y M02 | Dar por completo el flujo por suites limitadas; cambios/fallos sin caracterizar | 3/3/9 Alto | C06 revisar alcance/cobertura; AC-03/05/06 pruebas de dominio, serialización y arranque | B/T | Matriz, reporte por suite y fallos inducidos aislados; abierto |

Ocho riesgos: seis Alto, dos Medio, cero Bajo; niveles propuestos no sustituyen
severidades originales (3 Alta, 4 Media, 1 Baja) de los ocho hallazgos de Semana 13.

## Catálogo de controles (proceso propuesto y evidencia real)

| ID / tipo / propósito | Responsable propuesto | Evidencia exigida | Estado real en Semana 14 |
|---|---|---|---|
| C01 Preventivo: requisitos, contratos, capas y validación de entradas antes de cambio | B/T | RF/RNF, dependencias Domain, contrato revisado, casos de borde | Inspección documental realizada; validación CLI implementada/probada; REST/dominio completo pendientes |
| C02 Preventivo: revisión de acceso, datos y tenant antes de exposición | S/B | Política aprobada, controles y tests de autorización/ámbitos | Propuesto; no autorización/tenant QA completo implementado |
| C03 Preventivo: evidencia privada, retención e historia durable | B/S/T | Modelo, acceso, preservación y recuperación aislada | Propuesto; solo esquema de casos y memoria demostrados |
| C04 Preventivo/detectivo: criterios UX y pruebas manuales/asistivas | F/T | Tareas, esperado/observado, teclado, lector, zoom, viewport | Parcial: CDP/reflow actual; tareas/asistencia pendientes |
| C05 Preventivo/detectivo: checklist de PR, fuentes/versiones y revisión independiente | T/revisor | Diff ≤400, enlaces, dictamen y asignación | Checklist documental ejecutado por autora; revisión independiente pendiente |
| C06 Detectivo: suites, inspección de fallos/backlog y trazabilidad | T/B/F | Resultados separados PHP/HTML, mapa, métricas/desviaciones | Suites/inspección actuales realizadas; cobertura completa y seguridad pendientes |
| C07 Correctivo: registrar, priorizar, corregir por PR y verificar cierre | Corrector del ámbito + revisor | ID REV/AC, diff, pruebas repetidas y dictamen; reapertura si falla | Flujo propuesto; ocho AC pendientes, ningún cierre acreditado |

## RF/RNF y objetivos de calidad

Fuente: [Semana 2](../semana-02/rf-rnf-criterios.md). I=implementado y verificado
en CLI acotado, S=simulado parcial, D=documentado, P=pendiente. M02 no cuenta S/D.

| Requisito / objetivo de calidad | Métrica | Riesgo / control | Evidencia disponible / estado |
|---|---|---|---|
| RF-01 Caso completo guardado, rechazar incompleto | M01/M02/M07 | R08 / C01/C06 | ManualCase/DesignCase/tests y demo 201/422; I durante proceso; HTML S |
| RF-02 Campaña conserva casos seleccionados | M02/M07 | R03/R04 / C01/C03 | S12 matriz D y S11 asociación a campaña fija S; backend P |
| RF-03 Ejecución vinculada con observaciones/comparación | M01/M02/M07 | R03 / C01/C06 | HTML contexto/error/registro S; dominio P |
| RF-04 Resultado coherente con comparación | M01/M02/M07 | R03 / C01/C06 | HTML declara fallido/aprobado S; coherencia semántica backend P |
| RF-05 Evidencia identificada, ligada y consultable | M02/M05/M07 | R02/R04 / C02/C03 | Diálogo/referencia ficticia S; evidencia privada persistente P |
| RF-06 Defecto solo desde fallo, origen preservado | M01/M02/M07 | R03 / C01/C06 | HTML guarda D-demo y bloquea desde aprobado S; dominio P |
| RF-07 Consulta de toda la cadena e historial | M02/M04 | R04/R08 / C03/C06/C07 | Resumen de última ejecución S; consulta íntegra P |
| RF-08 Nuevas ejecuciones no alteran anteriores | M01/M02/M04 | R03/R04 / C03/C06/C07 | Arreglos JS S; suite no compara todos los datos previos; backend P |
| RNF-01 Integridad de vínculos navegables | M02/M05 | R02/R03/R04 / C02/C03/C06 | Contexto HTML S; integridad referencial persistente P |
| RNF-02 Consistencia de comparación y resultado | M01/M02/M07 | R03 / C01/C06 | Rechazo incompleto HTML S; coherencia de dominio P |
| RNF-03 Historia anterior idéntica tras reejecutar | M01/M02/M04 | R04 / C03/C06/C07 | D en S02/S12; igualdad histórica/durabilidad P |
| RNF-04 Contrato sustituible de registro de ejecución | M01/M02/M07 | R03/R08 / C01/C06 | D/P; suite Repository de casos no acredita ejecuciones |
| RNF-05 Un resultado único aprobado/fallido | M01/M02/M06 | R03/R06 / C01/C04/C06 | Selector/confirmaciones HTML S; invariante backend P |

M03 orienta toda la revisión de riesgos, M04 todo cierre de AC; M05 y R01/R02 son
controles transversales de seguridad, sin inventar RF nuevos. M06 no convierte UX
en RNF adicional. Ningún control/documento equivale a mitigación implementada completa.
