# Matriz de trazabilidad

Fuente: [RF/RNF Semana 2](../semana-02/rf-rnf-criterios.md) y
[arquitectura/trazabilidad Semana 4](../semana-04/trazabilidad.md).
IDs de casos en [plan](./plan-pruebas.md); resultados en [evidencias](./evidencias.md).
Vincular un requisito no significa que su alcance completo esté cerrado.

| Requisito auténtico | Componente / casos y evidencia | Resultado y alcance pendiente |
|---|---|---|
| RF-01 Crear ward | Ward::create U01; CatalogLaravelIntegrationTest, standalone | PASS en dominio/aplicación/PDO; P01 HTTP/UI pendiente |
| RF-02 Código ward único | wards_code_unique; I02 | PASS PostgreSQL; rechazo por escritura HTTP no disponible |
| RF-03 Crear cama relacionada | Bed::create U02; CatalogLaravelIntegrationTest; CN02 consulta relación | PASS dominio/PDO/lectura; P01 pendiente |
| RF-04 FK y unicidad por ward | CatalogService CB01; PostgreSQL I01/I03; CN04 contrato consulta | PASS en capas indicadas; CN04 no sustituye test de alta |
| RF-05 Estados ward | Ward U01/U03; wards_status_check I04 | active/inactive y rechazo broken comprobados; sin endpoint de cambio |
| RF-06 Estados cama | Bed U02; CatalogService CB02; beds_status_check I05 | Cuatro válidos y rechazo broken; sin política de transiciones clínicas |
| RNF-01 Integridad | I01–I05 sobre tablas existentes; SQLSTATE y originales preservados | PASS en cinco negativos; sin modificar esquema |
| RNF-02 Consistencia | CB02 impide insertar inválido; standalone fallo fake; integración revierte fixtures | Evidencia parcial; atomicidad de negocio P02 pendiente |
| RNF-03 Capas separadas | CatalogService y contratos mock CB01/CB02; provider/controlador/PDO inspeccionados | PASS de caminos probados; no prueba toda la mantenibilidad |
| RNF-04 Sustituibilidad | Mocks en CatalogRulesTest; fake HTTP y PDO en suites distintas | Contratos utilizados; paridad completa P04 pendiente |
| #11 / seguridad y consulta | CN01–CN04; CatalogHospitalIsolationTest y regresiones endpoint | PASS; contención #1154 temporal, no selección dinámica definitiva |

Las 3 U, 2 CB y 4 CN son nueve casos distintos. I01–I05 son cinco casos
adicionales de integración; no se cuentan de nuevo como unitarias o caja negra.
El plan SQA de #1364 se consultó desde su rama; no se modifica ni se incorpora
a esta entrega. Capacidad se registra sin inventar RF/RNF.
