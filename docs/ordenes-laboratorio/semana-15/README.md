# Semana 15 — Pruebas unitarias, caja blanca y caja negra

**Módulo:** Órdenes de laboratorio desde EMR · **ASII-16**  
**Autor:** Javier Alexander Fajardo López · `Javifa2324` · ASII-09  
**Fecha de ejecución:** 22-oct-2026  
**Flujo bajo prueba:** emisión de una orden desde el expediente y seguimiento de su estado.

## 1. Objetivo

Diseñar, ejecutar y documentar pruebas trazadas a los requisitos RF/RNF del módulo, sin probar
módulos ajenos:

- **Unitarias:** reglas de la orden, expediente, pruebas solicitadas, estados y trazabilidad.
- **Caja blanca:** ramas y condiciones del flujo de emisión, idempotencia, rollback y cancelación.
- **Caja negra:** clases de equivalencia, valores límite, permisos, aislamiento por tenant y errores
  seguros sobre la API.

## 2. Entregables

| Artefacto | Contenido |
|---|---|
| `tests/semana15.php` | 24 verificaciones: 10 unitarias, 3 de caja blanca y 11 de caja negra |
| `matriz_pruebas.md` | Matriz RF/RNF → prueba y cobertura |
| `defectos.md` | H-01 a H-04 con estado, evidencia y decisión |
| `evidencias/` | Salidas reales de cada comando y versión de PostgreSQL |
| `capturas/resultados.jpg` | Reporte visual generado desde las salidas anteriores |
| `verificar_contrato.py` | Verificación del contrato OpenAPI y de los límites de `reason` |
| `DECLARACION_IA.md` | Uso declarado de IA |

## 3. Entorno

- PHP 8.2+ sin framework; extensiones PDO SQLite y PDO PostgreSQL en el contenedor de pruebas.
- SQLite en memoria para la batería portable.
- PostgreSQL 16.15 real en el contenedor `bd-hospital`, puerto `55433`, base temporal
  `his_lab_orders_verify`.
- El PHP del host no tiene `pdo_pgsql`; por eso PostgreSQL se ejecuta con la imagen
  `localhost/mod09-php-pgsql`. Esto queda registrado como condición del entorno, no como omisión.

## 4. Comandos ejecutados

Desde `micro-his/ordenes-laboratorio/`:

```bash
php tests/semana15.php
php tests/run.php
php tests/mvc.php
php tests/api.php
php -S 127.0.0.1:8091 -t public public/index.php   # en otra terminal
php tests/client.php
```

Desde la raíz del repositorio:

```bash
python3 docs/ordenes-laboratorio/semana-15/verificar_contrato.py
```

Para ejecutar `run.php` contra PostgreSQL real:

```bash
podman exec bd-hospital psql -U postgres \
  -c "DROP DATABASE IF EXISTS his_lab_orders_verify;" \
  -c "CREATE DATABASE his_lab_orders_verify;"

podman run --rm --network host \
  -v "$PWD/micro-his/ordenes-laboratorio:/app:Z" -w /app \
  -e LAB_DB_DSN='pgsql:host=127.0.0.1;port=55433;dbname=his_lab_orders_verify' \
  -e LAB_DB_USER=postgres -e LAB_DB_PASSWORD=postgres \
  localhost/mod09-php-pgsql php tests/run.php
```

## 5. Resultados ejecutados

| Comando | Resultado | Alcance |
|---|---|---|
| `php tests/semana15.php` | **24/24** | Unitarias, caja blanca y caja negra de la semana 15 |
| `php tests/run.php` | **84/84** | 28 casos × InMemory, PDO/SQLite y PDO/PostgreSQL |
| `php tests/mvc.php` | **12/12** | Controlador, vista y presentador |
| `php tests/api.php` | **18/18** | Kernel HTTP, cabeceras y `problem+json` |
| `php tests/client.php` | **6/6** | Cliente HTTP contra servidor real |
| `verificar_contrato.py` | **6/6** | Respuestas OpenAPI y límites de `reason` |

Total de las baterías principales: **144 verificaciones aprobadas**. La verificación de contrato es
complementaria y no se suma como caso de prueba funcional.

La batería `run.php` creció de 25 a 28 casos por adaptador por los tres casos de regresión de H-01.
Las cifras anteriores de semanas 3, 6 y 7 describen ejecuciones históricas; la cifra vigente es la de
esta tabla.

## 6. Pruebas unitarias

| ID | Escenario | Resultado esperado | Requisito |
|---|---|---|---|
| U1 | Emisión con dos pruebas válidas | `ISSUED`, 2 ítems, 1 evento inicial | RF-OL-01, RF-OL-10 |
| U2 | Prueba repetida en la misma orden | `ORDER_ITEMS_INVALID` | RF-OL-01 |
| U3 | Salto `ISSUED → VALIDATED` | `INVALID_STATUS_TRANSITION` | RF-OL-06 |
| U4 | Cancelación con motivo y actor | `CANCELLED`; evento con actor y motivo | RF-OL-07, RF-OL-10 |
| U5 | Cancelar una orden ya validada | `ORDER_ALREADY_VALIDATED` | RF-OL-06, RF-OL-07 |
| U6 | Motivo vacío o solo espacios | `CANCELLATION_REASON_REQUIRED` | RF-OL-07 |
| U7 | Episodio cerrado | `CARE_EPISODE_NOT_OPEN` | RF-OL-02 |
| U8 | Prueba inactiva o inexistente | `LAB_TEST_NOT_ACTIVE` | RF-OL-03 |
| U9 | Consulta de la orden | Devuelve 2 ítems y 1 evento | RF-OL-05 |
| U10 | Listado filtrado por `ISSUED` | Solo órdenes `ISSUED` | RF-OL-08 |

## 7. Caja blanca

| ID | Rama o condición | Entrada | Resultado |
|---|---|---|---|
| W1 | Cortocircuito de idempotencia en `IssueLabOrder::executeOutcome()` | Misma clave dos veces | Misma orden; no se duplica |
| W2 | `catch` de `PersistenceFailure` y rollback | `failNextCreate = true` | Error de persistencia; el conteo no cambia |
| W3 | Condiciones de longitud del motivo | 4, 5, 250 y 251 caracteres | 4 y 251 → `CANCELLATION_REASON_INVALID`; 5 y 250 → cancelación válida |

La rama de error de persistencia se ejerce tanto en dominio (W2) como en la API (B11), donde se
traduce a `503 PERSISTENCE_FAILURE` sin traza.

## 8. Caja negra

| ID | Técnica | Escenario | Resultado |
|---|---|---|---|
| B1 | Camino feliz | `POST /api/v1/lab-orders` válido | `201` con ID |
| B2 | Idempotencia | Replay con la misma clave | `200` con la misma orden |
| B3 | Permisos | Sin `laboratorio.ordenes.crear` | `403 FORBIDDEN` |
| B4 | Permisos | Sin `laboratorio.ordenes.ver` | `403` |
| B5 | Aislamiento | Tenant B consulta orden de A | `404` sin revelar existencia |
| B6 | Valores límite | Motivo de 4, 251 y 5 caracteres | `422`, `422`, `200 CANCELLED` |
| B7 | Clases de equivalencia / límites | `per_page` 1, 50, 0 y 51 | `200`, `200`, `422`, `422` |
| B8 | Error seguro | Sin `Idempotency-Key` | `422 IDEMPOTENCY_KEY_REQUIRED` |
| B9 | Error seguro | Ruta y método desconocidos | `404` |
| B10 | Error seguro | Sin `X-Tenant-ID` | `400` `problem+json` con `request_id` |
| B11 | Falla de persistencia | `failNextCreate` durante el `POST` | `503 PERSISTENCE_FAILURE` sin traza |

Los casos B6 y W3 cubren los cuatro bordes del contrato: 4, 5, 250 y 251.

## 9. Trazabilidad

La matriz completa, con requisito, entrada, oráculo, evidencia y estado, está en
[`matriz_pruebas.md`](matriz_pruebas.md).

Cobertura directa de esta semana:

- **RF:** OL-01, OL-02, OL-03, OL-04, OL-05, OL-06, OL-07, OL-08, OL-09, OL-10, OL-11 y OL-12.
- **RNF:** OL-01, OL-02, OL-06, OL-07 y OL-09.
- **Fuera de esta batería:** RNF-OL-03 (medición de rendimiento de la semana 5), RNF-OL-04
  (revisión de datos clínicos), RNF-OL-05 (auditoría directa en base), RNF-OL-08 (UX de semanas
  8–11) y RNF-OL-10 (revisión arquitectónica de la semana 7).

## 10. Defectos

El detalle está en [`defectos.md`](defectos.md).

| ID | Hallazgo | Estado en semana 15 |
|---|---|---|
| H-01 | La API aceptaba motivos fuera de 5–250 | **Corregido y probado** |
| H-02 | OpenAPI omitía 400/403/503 | **Corregido y verificado** |
| H-03 | `X-Permissions` se confía en la API | Abierto; mitigado por gateway |
| H-04 | Replay con otra carga devuelve la orden previa | Abierto y aceptado |

## 11. Evidencias

- [`evidencias/semana15.txt`](evidencias/semana15.txt): 24 verificaciones.
- [`evidencias/run.txt`](evidencias/run.txt): 28 × 3 adaptadores, PostgreSQL incluido.
- [`evidencias/mvc.txt`](evidencias/mvc.txt), [`evidencias/api.txt`](evidencias/api.txt) y
  [`evidencias/client.txt`](evidencias/client.txt): baterías complementarias.
- [`evidencias/contrato.txt`](evidencias/contrato.txt): contrato y límites de `reason`.
- [`evidencias/resumen.txt`](evidencias/resumen.txt): conteo PASS/FAIL de todas las baterías.
- [`evidencias/postgres-version.txt`](evidencias/postgres-version.txt): PostgreSQL 16.15.
- [`capturas/resultados.jpg`](capturas/resultados.jpg): reporte visual construido desde esos logs.

La captura se regenera con:

```bash
NODE_PATH=/var/home/javier/.npm/_npx/668c188756b835f3/node_modules \
  node docs/ordenes-laboratorio/semana-15/capturar_resultados.cjs
```

## 12. Limitaciones y decisiones

- Los dobles de expediente y catálogo son locales; no se probaron los módulos dueños de esos datos.
- H-03 y H-04 siguen abiertos y se registran como riesgo, no como corrección.
- La prueba B7 inyecta `per_page` en la entrada del kernel; la decodificación de query string del
  front controller no se prueba en esta batería.
- El contrato OpenAPI se verifica por script con PyYAML; no se generó un cliente desde el contrato.
- La captura muestra las salidas reales de los archivos de evidencia; no se transcribieron resultados
  no ejecutados.

## 13. Criterios de aceptación

- [x] Mínimo 3 unitarias: hay 10.
- [x] Mínimo 2 de caja blanca: hay 3.
- [x] Mínimo 4 de caja negra: hay 11.
- [x] Matriz RF/RNF → prueba.
- [x] Comandos y resultados reproducibles.
- [x] Captura de evidencia.
- [x] Defectos registrados con estado.

## 14. Declaración de IA

Ver [`DECLARACION_IA.md`](DECLARACION_IA.md).
