# Guion de la defensa — Catálogo de pruebas (ASII-17)

1. **Problema** (1 min): una orden de laboratorio necesita saber exactamente qué muestra tomar y con qué rangos interpretar el resultado. Si el catálogo cambia en silencio, el resultado se interpreta mal.
2. **Tesis** (1 min): una versión nace válida o no nace y nunca cambia; los consumidores guardan su `lab_test_id`.
3. **Del UML al código** (2 min): recorrido del diagrama trazable. UC-LAB-01 → RF-LAB-01..05 → `LabTest::register` → `RegisterLabTest` → `LabTestRepository` → `POST /api/v1/lab-tests`.
4. **SOLID** (1 min): OCP en los tipos de resultado; la demo de la Semana 2 lo prueba sin tocar el validador.
5. **Capas y Repository** (2 min): el dominio no conoce PDO ni HTTP; la misma batería corre con PDO e InMemory.
6. **Cliente-servidor** (1 min): contrato OpenAPI, `problem+json`, un reintento seguro y `CatalogUnavailable`. Decisión: no separarlo todavía como microservicio.
7. **Caso práctico** (2 min): la proteinuria ordinal. Tabla de impacto por capa; los consumidores no cambian.
8. **Riesgos abiertos** (30 s): migración de `lab_tests` y `result_scale`, que se acuerdan con Javier.

**Preguntas que preparo:**
- ¿Por qué el solapamiento de vigencia no es una regla del dominio? Porque depende de otras filas y de la concurrencia: lo decide la base (`UNIQUE` y GiST).
- ¿Qué pasa si el catálogo cae? Órdenes no ordena a ciegas: el cliente lanza `CatalogUnavailable`.
