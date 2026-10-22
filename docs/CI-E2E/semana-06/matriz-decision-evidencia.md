# Matriz decisión → evidencia

| # | Decisión | Requisito | Evidencia verificable | Estado |
|---|---|---|---|---|
| D1 | Veredicto de tres valores (`approved`/`rejected`/`blocked`) en lugar de pasa/falla | RF-05 | W01–W18; CI14 (exit 0), CI17 (exit 1), CI18 (exit 2) | Verificado en local |
| D2 | «Vacío no es éxito»: suite sin pruebas ejecutadas o sin reporte bloquea | RF-05, RNF-03 | W05, W06, CI18, E2E-QA-03 | Verificado en local |
| D3 | Omitidas no cuentan contra la tasa; tienen su propia regla | RF-05 | W12, W13, U15 | Verificado en local |
| D4 | DIP: regla pura en el dominio, detalles como adaptadores | RNF-06 | AR01–AR03, A01/P01/A11 | Verificado en local |
| D5 | Repository con dos adaptadores y aislamiento por tenant dentro de la consulta | RF-06 | P01–P09, BN11, A08 | Verificado en local |
| D6 | Resultados y decisiones de solo inserción | RNF-03 | U19, P04, P05, A04 | Verificado en local |
| D7 | Falla cerrado ante RBAC o almacén caídos | RNF-01 | BN22, BN23, P11, E2E-QA-07 | Verificado en local |
| D8 | Auditoría por Gobernanza; seguridad aborta si no se guarda | RNF-04 | A13–A16 | Verificado en local |
| D9 | JUnit como formato único de todas las etapas | RF-07 | CI01–CI06; `junit-command.php` | Verificado en local |
| D10 | Contrato de rutas sobre todo el HIS | RF-08 | CI10–CI13; 17/17 rutas de develop | Verificado en local |
| D11 | E2E de pila completa en PostgreSQL efímero con semillas ficticias | RNF-02 | `tests/E2E/auth-rbac.e2e.mjs`, job `e2e` | **Preparado, no ejecutado**: necesita GitHub Actions (aquí no hay PostgreSQL de prueba) |
| D12 | Pint y la suite Database informativos (`continue-on-error`) | RNF-05 | Pint sobre develop: 212 archivos previos sin formato; Database sin ejecución verde todavía | Propuesta pendiente de aprobación del grupo |
| D13 | No separar en microservicio | — | Criterios medibles en [semana-05](../semana-05/README.md#evaluación-de-la-frontera-de-microservicio-qa-registry) | Decidido |
