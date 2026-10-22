# Matriz de riesgos — ASII-17

| # | Riesgo | Probabilidad | Impacto | Mitigación | Seguimiento |
|---|---|---|---|---|---|
| R1 | Una orden usa una versión distinta a la vigente | Media | Alto | Versiones inmutables; los consumidores guardan el `lab_test_id` | Prueba de contrato con Órdenes en la Semana 16 |
| R2 | `lab_tests` sin migración real | Alta | Alto | Acordar el Anexo A con Javier | RT-04 de la Semana 13 |
| R3 | Dos altas simultáneas con vigencias solapadas | Baja | Medio | `UNIQUE` + exclusión GiST en PostgreSQL | Prueba concurrente en la Semana 15 |
| R4 | Catálogo caído durante la toma de muestras | Media | Alto | `CatalogUnavailable` y no ordenar a ciegas | Paso «servidor caído» del prototipo |
