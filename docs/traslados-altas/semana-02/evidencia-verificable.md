# Evidencia verificable — Semana 2

**Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Diseño:** [solid-lsp.md](./solid-lsp.md) · **Requisitos:** [rf-rnf-criterios.md](./rf-rnf-criterios.md).

## Alcance de la demo

| Comprobado en memoria | No comprobado |
|---|---|
| El traslado del diseño antes contradice el contrato de `AltaHospitalaria`. | Autorización previa y roles concretos. |
| Traslado y alta pasan la misma batería del contrato común. | Transacciones, atomicidad, bloqueos `FOR UPDATE` y concurrencia reales. |
| Un cliente común trata igual ambas operaciones sin nombrarlas. | Exclusión GiST, índice único y triggers ejecutados en PostgreSQL. |
| Resultados específicos, rechazos sin cambios y `beds.status` intacto. | Desenlaces reales de `COMMIT`; fallo e incertidumbre son simulaciones. |

## Archivos y simplificaciones

- [`evidencia/lsp-traslado-alta.php`](./evidencia/lsp-traslado-alta.php): solicitudes, resultados, contrato común y ambas operaciones.
- [`evidencia/antes.php`](./evidencia/antes.php): ejemplo didáctico con las firmas del diseño antes.
- [`evidencia/demo-lsp.php`](./evidencia/demo-lsp.php): adaptador, datos ficticios, cliente común y comprobaciones.

`PuertoHospital` se reduce a `leer()`, `ahora()` y `confirmar(estadoNuevo)`. Cada operación calcula un estado nuevo sobre una copia y solo lo entrega al adaptador si no hay rechazo. Esto permite comprobar el contrato en memoria, pero **no demuestra** persistencia, bloqueos, atomicidad ni transacciones reales. El adaptador etiqueta expresamente como simulados los desenlaces de `COMMIT`.

## Validación ejecutada

Ejecutada localmente en Windows con `PHP 8.2.12 (cli)`. Estos resultados no son la ejecución reportada por Claude en el ZIP.

```text
php -v
PHP 8.2.12 (cli) (built: Oct 24 2023 21:15:15) (ZTS Visual C++ 2019 x64)
Código de salida: 0

php -l docs/traslados-altas/semana-02/evidencia/lsp-traslado-alta.php
No syntax errors detected in docs/traslados-altas/semana-02/evidencia/lsp-traslado-alta.php
Código de salida: 0

php -l docs/traslados-altas/semana-02/evidencia/demo-lsp.php
No syntax errors detected in docs/traslados-altas/semana-02/evidencia/demo-lsp.php
Código de salida: 0

php -l docs/traslados-altas/semana-02/evidencia/antes.php
No syntax errors detected in docs/traslados-altas/semana-02/evidencia/antes.php
Código de salida: 0

php docs/traslados-altas/semana-02/evidencia/demo-lsp.php
38 comprobaciones PASS
Todas las comprobaciones se cumplieron.
Código de salida: 0
```

La demo termina con código `1` ante cualquier `FAIL`, incluida la ausencia de la contradicción esperada en el diseño antes.

## Salida de la demo

```text
== Contrato común: TrasladoInterno ==
PASS  ExitoConfirmado contiene una LiberacionDeCama
PASS  S1 termina en T; la liberación informa cama A, estancia S1 y T
PASS  como máximo una estancia abierta en E1
PASS  beds.status intacto: A sigue occupied (liberar ≠ available)
PASS  EPISODIO_NO_INPATIENT, sin cambios
PASS  EPISODIO_NO_ABIERTO, sin cambios
PASS  SIN_ESTANCIA_ABIERTA, sin cambios
PASS  ESTANCIA_DESACTUALIZADA, sin cambios
PASS  T_INVALIDA, sin cambios
PASS  COMMIT rechazado (SIMULADO): FalloConfirmado, sin cambios
PASS  conexión perdida (SIMULADA): ResultadoIncierto, nunca éxito

== Contrato común: AltaHospitalaria ==
PASS  ExitoConfirmado contiene una LiberacionDeCama
PASS  S1 termina en T; la liberación informa cama A, estancia S1 y T
PASS  como máximo una estancia abierta en E1
PASS  beds.status intacto: A sigue occupied (liberar ≠ available)
PASS  EPISODIO_NO_INPATIENT, sin cambios
PASS  EPISODIO_NO_ABIERTO, sin cambios
PASS  SIN_ESTANCIA_ABIERTA, sin cambios
PASS  ESTANCIA_DESACTUALIZADA, sin cambios
PASS  T_INVALIDA, sin cambios
PASS  COMMIT rechazado (SIMULADO): FalloConfirmado, sin cambios
PASS  conexión perdida (SIMULADA): ResultadoIncierto, nunca éxito

== Resultados específicos ==
PASS  traslado: ResultadoTraslado y E1 sigue open
PASS  traslado: destino B en [T, ∞), mismo episodio y contiguo a S1
PASS  traslado a Z: DESTINO_INEXISTENTE, sin cambios
PASS  traslado a A: DESTINO_IGUAL_ORIGEN, sin cambios
PASS  traslado a C: DESTINO_NO_AVAILABLE, sin cambios
PASS  traslado a D: DESTINO_SOLAPADO, sin cambios
PASS  alta: ResultadoAlta y E1 closed en T
PASS  alta: ninguna estancia abierta y ninguna creada
PASS  solicitud inválida: ESTANCIA_ESPERADA_REQUERIDA, sin operación
PASS  solicitud inválida: DESTINO_REQUERIDO, sin operación
PASS  solicitud inválida: REASON_VACIO, sin operación

== Cliente común con ambas operaciones ==
      traslado → Cama A sin ocupación del episodio desde 12:00; su disponibilidad la decide el catálogo de camas
      alta     → Cama A sin ocupación del episodio desde 12:00; su disponibilidad la decide el catálogo de camas
PASS  el aviso es idéntico con traslado y con alta
PASS  el cliente atiende un rechazo sin conocer la operación
PASS  AvisoOcupacionLiberada no nombra operaciones ni resultados específicos

== Antes (EJEMPLO DIDÁCTICO): TrasladoInterno extends AltaHospitalaria ==
PASS  con AltaHospitalaria el cliente informa «episodio E1 cerrado, sin cama» y se cumplen sus postcondiciones
PASS  incumplimiento esperado: el cliente informa «episodio E1 cerrado, sin cama», pero E1 sigue open en B

Todas las comprobaciones se cumplieron.
```

## Trazabilidad RF, reglas y evidencia

| RF y CA | Reglas de Semana 1 | Evidencia o estado |
|---|---|---|
| RF-TA-01, RF-TA-02 · CA-01 | R-TA-02, R-TA-03 | Tres construcciones inválidas; no se crea operación. |
| RF-TA-03 · CA-02 | R-TA-07 y excepción de autorización | **No cubierto:** autorización es precondición de la demo. |
| RF-TA-04 · CA-03 a CA-06 | R-TA-03, R-TA-04, R-TA-06, R-TA-07 | Cinco rechazos comunes sin cambios, en ambas operaciones. |
| RF-TA-05 · CA-07 | R-TA-01, R-TA-03, R-TA-05 | Destinos Z, A, C y D cubren los cuatro rechazos. |
| RF-TA-06 · CA-08 | R-TA-02, R-TA-04, R-TA-05, R-TA-06 | Traslado mantiene E1 abierto y crea destino contiguo en B. |
| RF-TA-07 · CA-09 | R-TA-03, R-TA-04, R-TA-06 | Alta cierra E1 en T, sin estancias abiertas ni nuevas. |
| RF-TA-08 · CA-12, CA-13 | R-TA-06, R-TA-07 | `FalloConfirmado` y `ResultadoIncierto`, ambos **simulados**. |
| RF-TA-09 · CA-10 | R-TA-06 | Misma batería y cliente común para ambas operaciones. |
| RF-TA-10 · CA-11 | R-TA-01 | `beds.status` permanece intacto. |

## Trazabilidad RNF

| RNF | Evidencia o estado |
|---|---|
| RNF-TA-01 | Misma batería y cliente sin distinguir operaciones concretas. |
| RNF-TA-02 | Todos los rechazos comparan el estado con su instantánea anterior. |
| RNF-TA-03 | **No cubierto:** la copia en memoria no demuestra atomicidad real. |
| RNF-TA-04 | **No cubierto:** no hay ejecución concurrente ni bloqueos reales. |
| RNF-TA-05 | Modelado en memoria y revisión estática del esquema; PostgreSQL no se ejecutó. |
| RNF-TA-06 | Respuesta incierta y no éxito comprobados con simulación. |
| RNF-TA-07 | Solo identificadores y datos ficticios. |
| RNF-TA-08 | Matrices RF/CA/reglas y RNF de este documento. |
