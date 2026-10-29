# Reportes y analytics básicos — Semana 2 (LSP)

**Módulo:** `ASII-23` · **Issue:** `Refs #152` · **Estudiante:** `Yamilet1235`.
Semana 1 define el proceso y el indicador `IND-RA-01` (fórmula `TCI-1.0`,
`100 × N_cancelados / N_finalizados`, fuente `care_episodes`, filtros periodo
`[desde, hasta)` y `service_code` opcional). Esta semana no modifica ese
contrato: lo conserva como base común de las variantes.

## RF y RNF de la semana

**RF (este PR):**
- `RF-RA-S2-1` Realizar el cálculo de `IND-RA-01` a partir de una lectura de
  conteos coherente vía un contrato único (`PercentageCalculator` + `SummaryReader`).
- `RF-RA-S2-2` Sustituir el lector de agregados (en memoria u otro origen) sin
  alterar precondiciones, postcondiciones, unidad ni estados del contrato.
- `RF-RA-S2-3` Informar estados sin porcentaje: `SIN_DATOS_EN_PERIODO`,
  `LECTURA_INCOHERENTE`, `SIN_DATOS`, igual que la narrativa de Semana 1.

**RNF:**
- División entre cero prohibida: cuando el denominador vale `0`, el estado es
  “sin datos del periodo” y no se calcula porcentaje.
- Unidad única (porcentaje con dos decimales, `PHP_ROUND_HALF_UP`) para toda
  implementación sustituible.
- Ningún cálculo parcial ante lectura incoherente (`N_cancelados > N_finalizados`).

## Criterios de aceptación

| Id | Criterio | Verificación |
|---|---|---|
| `CA-S2-1` | Antes: dos subclases de la misma base rompen el contrato (precondición extra que lanza con denominador 0; unidad distinta del porcentaje) | Bloque “ANTES” del ejemplo ejecutable |
| `CA-S2-2` | Después: implementaciones sustituibles devuelven el mismo porcentaje unitario (`25.00`) sobre la misma lectura | Bloque “DESPUES” del ejemplo ejecutable |
| `CA-S2-3` | Con denominador 0 el calculador no lanza y devuelve `SIN_DATOS_EN_PERIODO` | Bloque “DESPUES” |
| `CA-S2-4` | Servicio inexistente → `SIN_DATOS`; lectura incoherente → `LECTURA_INCOHERENTE` sin porcentaje | Bloque “DESPUES” |

## Diseño antes/después

**Responsabilidades (antes):**
- `RigidCancellationCalculator` (abstracta) agrupa lectura y formateo.
- `InpatientCancellationRate` añade una precondición oculta (denominador > 0
  obligatoria) y lanza `RuntimeException` inesperado.
- `CancelledRatioOfInpatient` devuelve proporción (no porcentaje) y no respeta
  el estado “sin datos”: unidad distinta y postcondición incompatible.

**Responsabilidades (después):**
- `SummaryReader`: única responsabilidad, leer agregados `N_finalizados` y
  `N_cancelados` (con validación de coherencia).
- `PercentageCalculator`/`CancellationRateCalculator`: calcular el porcentaje
  con unidad fija y estados del contrato; no conoce el origen físico de los datos.
- La variante del filtro (`service_code`) es **configuración del lector**, no
  una subclase que restringe condiciones.

**Dependencias:**

| Antes | Después |
|---|---|
| Subclase → SQL/lectura + cálculo + unidades (acoplado) | `Calculator → interface SummaryReader` (DIP) |
| El dominio depende de la fuente concreta | El adaptador de persistencia implementa el puerto |

## Ejemplo ejecutable de sustitución

```bash
php micro-his/reportes-analytics/tests/example-lsp.php
# LSP ejemplo OK: 7 comprobaciones; misma unidad (porcentaje con 2 decimales),
# misma unidad para sustituciones y no lanzamiento con denominador 0.
```

Evidencia verificada con el comando anterior (PHP 8.3.33, local):

- Antes: sobre la misma lectura, `InpatientCancellationRate` exige un
  denominador > 0 adicional (lanza), y `CancelledRatioOfInpatient` devuelve
  una unidad distinta (`0.0000`) — sustitución inválida.
- Después: `CancellationRateCalculator` y su variante de servicio comparten
  el mismo contrato (`IndicatorReading`), misma unidad y estados; el mismo
  lector en memoria o un lector nulo conservan los resultados válidos; el
  denominador 0 se convierte en estado “sin datos del periodo” sin lanzar.

## Archivos de la semana

- `docs/reportes-analytics/semana-02/README.md` (este documento).
- `docs/reportes-analytics/semana-02/DECLARACION_IA.md`.
- `micro-his/reportes-analytics/tests/example-lsp.php` (ejemplo ejecutable).

## Limitaciones

- El ejemplo usa lecturas simuladas (dobles) y aún no consulta la base
  HOSPITAL; esa integración es la entrega de Semana 3 y la funcional de
  Semana 16.
- La unidad “porcentaje con 2 decimales” hereda la propuesta de Semana 1;
  no hay definición oficial del docente.
