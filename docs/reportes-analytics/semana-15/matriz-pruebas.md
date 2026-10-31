# ASII-23 Semana 15 — Matriz de pruebas

**Issue:** `Refs #152`. Resultados de la ejecución local del 30/10/2026 sobre PostgreSQL 17 aislado, con datos ficticios. GitHub Actions: **no ejecutado** por el bloqueo de facturación.

Tipos: **U** unitaria, **W** caja blanca (recorre una rama concreta del código), **B** caja negra (solo contrato observable).

## Matriz requisito → prueba

| ID | Tipo | Requisito | Prueba | Resultado |
|---|---|---|---|---|
| U1 | U | N/A nunca se muestra como 0 % | `reportes.test.mjs`: `porcentajeLegible` | OK |
| U2 | U | Filtros inválidos, incluidas fechas imposibles | `reportes.test.mjs`: `errorDeFiltros` | OK |
| U3 | U | Mensajes sin detalle interno | `reportes.test.mjs`: `mensajeDeError` | OK |
| U4 | U | Contraste de los colores | `semana-09/evidencia/contraste.mjs` | OK, 7 de 7 |
| W1 | W | Rama de denominador cero | `run.php`: sin datos | OK |
| W2 | W | Rama de lectura incoherente | `run.php`, con doble | OK |
| W3 | W | Rama de ida y vuelta en `Period::instant` | `run.php`: día inexistente, mes 13, hora 25 | OK |
| W4 | W | Conexión perezosa: base caída responde 503 y no 500 | `test_una_base_hospital_inexistente_responde_503_y_nunca_ceros` | OK |
| B1 | B | Fórmula: 25.00 con 4 y 1 | `run.php`; API real; recorrido en Chrome | OK |
| B2 | B | Filtro de sala: 33.33 | `run.php`; recorrido en Chrome | OK |
| B3 | B | Cero real: 0.00 | `run.php`; API real | OK |
| B4 | B | Límites `[desde, hasta)` | API real, sembrado en los bordes | OK |
| B5 | B | Periodo inválido: 422 sin cifra | `IndicatorEndpointTest` | OK |
| B6 | B | Sin sesión 401; sin permiso 403; método incorrecto 405 | `IndicatorEndpointTest` | OK |
| B7 | B | Otro hospital: 403 sin leer | `test_miembro_de_otro_hospital_…`; recorrido real | OK |
| B8 | B | Sin datos personales en la respuesta | `IndicatorHospitalIntegrationTest` | OK |
| B9 | B | Pantalla: filtros, errores, 375 px, recepción | `semana-11/evidencia/recorrido-e2e.mjs` | OK, 15/15 |
| B10 | B | Móvil a 320, 375, 430 y 768 px | `semana-10/evidencia/movilidad.mjs` | OK, 15/15 |
| R1 | Regresión | Suite completa de Laravel | `php artisan test` | 1 falla ajena (PublishedContractsTest, por diagnosticos.png de ASII-11); 546 correctas de 547 |

## Capturas

Están en [`../semana-11/capturas/`](../semana-11/capturas/) y [`../semana-10/capturas/`](../semana-10/capturas/). Son de Chrome headless, no de una revisión humana.

## Defectos

| ID | Defecto | Severidad | Hallado en | Estado |
|---|---|---|---|---|
| D1 | Un miembro válido del hospital B obtenía el 25.00 % del hospital A | Crítica | Auditoría, recorrido real | Corregido, #1561 |
| D2 | `2026-02-31` y el mes 13 respondían 200 | Media | Auditoría, API | Corregido, #1565 |
| D3 | La cifra anterior quedaba junto a un error | Alta | Auditoría, Chrome | Corregido, #1565 |
| D4 | Formulario sin bordes y botón parecido a texto | Alta | Auditoría, Chrome | Corregido, #1565 |
| D5 | Sin aviso de que «Hasta» no se incluye | Alta | Auditoría, Chrome | Corregido, #1565 |
| D6 | Mensaje de error al cargar la página | Media | Auditoría, Chrome | Corregido, #1565 |
| D7 | El manual citaba un mensaje que no aparecía | Baja | Auditoría | Corregido, #1565 |
| D8 | Texto dañado en documentos y código | Baja | Auditoría | Corregido, #1565 y entregas posteriores |
| D9 | Dependencia de filas previas en `local_patients` | Media | Entorno efímero, 26/10 | Corregido, semilla propia en la prueba |
| D10 | Ningún módulo escribe `cancelled` | Media | Auditoría | **Abierto**, requisito por confirmar |

## Reproducir

```bash
php micro-his/reportes-analytics/tests/run.php
php micro-his/reportes-analytics/tests/example-lsp.php
php artisan test tests/Feature/Reportes
node resources/js/modules/reportes/reportes.test.mjs
node resources/js/modules/reportes/reportes.a11y.test.mjs
```

Las pruebas de `tests/Database` y `IndicatorHospitalIntegrationTest` requieren PostgreSQL de pruebas; ver `phpunit.xml`.
