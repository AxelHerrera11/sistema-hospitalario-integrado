# Orden de revisión del módulo

Semanas 1–4 y #841 ya están integrados en develop.

La pila comienza en #692 sobre develop actualizado. La documentación de semana 5 se separó para cumplir el máximo de 400 líneas por PR. Se conservan los cálculos dinámicos de rollback de develop. Las capturas y la receta reproducible están incluidas desde #697.

| Orden | PR | Entrega | Base |
|---:|---|---|---|
| 1 | #692 | Semana 5 (2/3) — Recepción de muestras: persistencia clínica PostgreSQL | develop |
| 2 | #1315 | Semana 5 (3/3) — Recepción de muestras: documentación y contrato de persistencia | #692 |
| 3 | #693 | Semana 6 — Recepción de muestras: caso práctico API Laravel, JWT y RBAC | #1315 |
| 4 | #694 | Semana 7 — Recepción de muestras: componentes y proyección de órdenes | #693 |
| 5 | #695 | Semana 8 — Recepción de muestras: flujo por rol y pantalla Vue funcional | #694 |
| 6 | #696 | Semana 9 — Recepción de muestras: usabilidad y accesibilidad verificadas | #695 |
| 7 | #697 | Semana 10 — Recepción de muestras: adaptación responsive del flujo clínico | #696 |
| 8 | #698 | Semana 11 — Recepción de muestras: prototipo navegable y pruebas HTTP reales | #697 |
| 9 | #699 | Semana 12 — Recepción de muestras: recuperación idempotente e impresión | #698 |
| 10 | #700 | Semana 13 — Recepción de muestras: revisión del contrato y actor clínico | #699 |
| 11 | #701 | Semana 14 — Recepción de muestras: SQA, concurrencia PostgreSQL y CI | #700 |
| 12 | #702 | Semana 15 (1/2) — Recepción de muestras: pruebas clínicas y custodia inmutable | #701 |
| 13 | #839 | Índice y evidencia de validación | #702 |

Integrar en este orden, después de la revisión correspondiente. El runtime está en app/Modules/Reception y resources/js/modules/recepcion-muestras. El harness de navegador usa dobles HTTP explícitos; las pruebas de API usan identidad y PostgreSQL según su configuración documentada.

Requisitos de despliegue: RECEPTION_HOSPITAL_ID, actores en local_user_refs, órdenes confiables importadas con reception:import-order y publicación institucional de outbox_events.
