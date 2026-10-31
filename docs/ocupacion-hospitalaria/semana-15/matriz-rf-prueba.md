# Matriz RF/RNF → prueba

Trazabilidad de los requisitos de la [semana 2](../semana-02/rf-rnf-criterios.md) con las
pruebas ejecutadas. La columna «cobertura» indica cómo se verifica cada requisito.

| Requisito | Criterio de aceptación | Pruebas que lo cubren | Cobertura |
|---|---|---|---|
| RF-OCC-01 | Con sesión, hospital y permiso válidos responde un resumen fechado; si falta alguno, rechaza antes de consultar el inventario | `test_devuelve_la_fotografia_de_ocupacion_con_el_corte_y_el_request_id`, `test_sin_token_responde_401`, `test_sin_el_permiso_ocupacion_ver_responde_401`, `test_un_hospital_distinto_al_declarado_responde_403` | Completa |
| RF-OCC-02 | Una estancia futura o terminada en `T` no ocupa; una que contiene `T` sí ocupa | `test_bed_is_occupied_only_inside_the_stay_interval`, `test_open_stay_keeps_the_bed_occupied_without_end_date` | Completa |
| RF-OCC-03 | Con estancia activa se clasifica ocupada; sin estancia usa `available`, `maintenance` o `inactive`; `occupied` sin estancia queda no verificada; la suma coincide con el inventario | `test_operational_status_does_not_change_the_interval_derived_occupancy`, `cada cama se cuenta exactamente una vez`, `occupied sin estancia queda no verificada` | Completa |
| RF-OCC-04 | Los totales globales equivalen a la suma por sala y la tasa se redondea a dos decimales | `consolida las salas sin perder camas`, `la tasa usa solo camas operativas`, `test_occupancy_counters_ignore_beds_out_of_service` | Completa |
| RF-OCC-05 | Una sala válida limita los agregados; una sala ajena o inexistente se rechaza sin revelar datos | — | **No cubierta**: el filtro por sala no está implementado en el contrato v1 (propuesta R-3 de la semana 13) |
| RF-OCC-06 | Sin camas devuelve cantidades en cero; si `ocupadas + disponibles = 0`, la tasa es no aplicable | `sin capacidad operativa la tasa es null` | Completa |
| RF-OCC-07 | Si la base HOSPITAL falla, no presenta cifras parciales y comunica indisponibilidad | `test_una_base_hospital_inexistente_responde_503`, `test_la_falla_de_persistencia_responde_503`, `la falla devuelve 503 y nunca ocupación cero` | Completa |
| RNF-OCC-01 | El 100 % de pruebas sin sesión, permiso o tenant válido falla sin devolver cifras | `test_sin_token_responde_401`, `test_sin_el_permiso_ocupacion_ver_responde_401`, `test_un_hospital_distinto_al_declarado_responde_403`, `test_un_hospital_distinto_al_declarado_no_ve_ocupacion` | Completa |
| RNF-OCC-02 | Todas las salas y el global incluyen el mismo `cutoff_at` | `test_devuelve_la_fotografia_de_ocupacion_con_el_corte_y_el_request_id`, `responde 200 con la misma fecha de corte` | Completa |
| RNF-OCC-03 | La suma de categorías equivale siempre a `total_beds` | `test_occupancy_counters_ignore_beds_out_of_service`, `cada cama se cuenta exactamente una vez` | Completa |
| RNF-OCC-04 | El esquema de salida no contiene datos de paciente o episodio | `test_la_respuesta_no_expone_datos_de_pacientes`, `test_los_errores_no_exponen_detalles_internos` | Completa |
| RNF-OCC-05 | Con hasta 10 000 camas el resumen responde en ≤ 1 s en p95 | — | **Sin datos**: no hay entorno con ese volumen |
| RNF-OCC-06 | En el 100 % de fallas simuladas se muestra indisponibilidad, no un vacío exitoso | `test_una_base_hospital_inexistente_responde_503`, `test_la_falla_de_persistencia_responde_503` | Completa |
| RNF-OCC-07 | Cada indicador incluye etiqueta y valor textual y el foco recorre filtros y resultados | `ocupacion.test.mjs` (14 aserciones de presentación) | Parcial: sin lector de pantalla ni navegador objetivo |
| RNF-OCC-08 | Los consumidores dependen de interfaces pequeñas | `Domain y Application no dependen de PDO`, `Presentation no contiene SQL` | Completa |

## Resumen

- Requisitos con cobertura completa: **11 de 15**.
- Cobertura parcial: **1** (accesibilidad, por inspección y aserciones de presentación).
- Sin cobertura por funcionalidad pendiente: **2** (filtro por sala y medición de volumen).
- Ambos casos sin cobertura están declarados como pendientes en el plan SQA de la semana 14 y en
  la revisión técnica de la semana 13; ninguno se reporta como «cumple».
