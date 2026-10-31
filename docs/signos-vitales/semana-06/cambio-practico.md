# Registro del cambio práctico defendido — unidades de medida

## El pedido

> «El hospital incorpora un glucómetro que reporta en mmol/L y termómetros en °F. El médico debe registrar en la unidad del equipo, sin alertas falsas y sin ocultar las reales.»

El cambio cruza las cinco áreas del enunciado: **signos vitales** (qué se mide), **unidades** (en qué), **umbrales** (contra qué se compara), **valores anormales** (qué se declara anormal) y **alertas clínicas** (qué se notifica).

## Qué pasa hoy

El contrato fija las unidades implícitas (°C, lpm, rpm, mmHg, %, kg, cm y mg/dL) y `vital_signs` no guarda unidad (riesgo R3 de la [Semana 1](../semana-01/narrativa-alcance-trazabilidad.md)). Si un cliente envía glucosa en mmol/L, el valor pasa la plausibilidad (cualquier número entre 0 y 2000) y se evalúa como mg/dL. Medido con `GlucoseRule` (`crit ≤ 53`, `warn ≤ 69` o `≥ 181`, `crit ≥ 400`):

| Glucosa enviada | Leída como mg/dL (hoy) | Valor real en mg/dL | Severidad real |
|---|---|---|---|
| 5,5 mmol/L (normal) | **Critical** | 99,1 | Ninguna |
| 12 mmol/L | **Critical** | 216,2 | Warning |
| 3,0 mmol/L | **Critical** | 54,0 | Warning |
| 2,9 mmol/L | Critical | 52,2 | Critical |

Un valor normal genera una alerta crítica falsa a los médicos tratantes. Es un fallo de alarma excesiva: erosiona la confianza en las alertas reales. La temperatura en °F falla seguro (98,6 °F es 422 `VITAL_SIGN_IMPLAUSIBLE`) pero deja la captura inutilizable.

## Alternativas

| Opción | Veredicto |
|---|---|
| A. Una regla por unidad (`GlucoseMmolRule`) | **Descartada.** Duplica los umbrales: dos fuentes de verdad que divergen. Cada unidad nueva obliga a otra clase y al `ThresholdEvaluator` a conocerla. |
| B. Columna `unit` en `vital_signs` | **Descartada.** Es una migración sobre una tabla de otro esquema, y las reglas tendrían que consultar la unidad de cada fila. Cambia a Notas SOAP y a los paneles que leen la tabla. |
| C. Normalizar en la frontera a una unidad canónica | **Elegida.** Los umbrales, el evaluador, el Repository y la base no cambian. |

## Diseño elegido

- `Unit` (Domain): unidad conocida de un signo, con su factor de conversión a la canónica (°F → °C; mmol/L → mg/dL × 18,016).
- `Measurement` (Domain): signo, valor y unidad. Convierte a canónico **antes** de construir `VitalSigns`, así la plausibilidad también protege la conversión (122 °F son 50 °C → 422).
- Contrato **aditivo** en `/api/v1`: un número sigue significando la unidad canónica (los clientes actuales no cambian); un objeto `{ "value": 5.5, "unit": "mmol/L" }` declara la unidad. Unidad desconocida para ese signo: `422 UNIT_NOT_SUPPORTED`.
- La respuesta y la alerta siempre expresan el **valor canónico**, para que dos clientes distintos vean lo mismo.

## Impacto por capa

| Capa | Cambia | No cambia |
|---|---|---|
| Presentation | `Request::measurements()` reconoce número u objeto `{value, unit}` | `Router`, `ApiKernel`, `JsonView` |
| Application | `RecordVitalSigns` recibe medidas y normaliza (una línea más) | Orquestación, puertos, `GetVitalSign` |
| Domain | **Nuevos** `Unit` y `Measurement` | `VitalSigns`, `VitalSignRule`, `RangeRule` y sus subtipos, `ThresholdEvaluator`, `Assessment` |
| Persistence | Nada | `PdoVitalSignRepository`, `InMemoryVitalSignRepository` |
| Base de datos | Nada: se guarda el valor canónico | `vital_signs` sin migración |
| Contrato | OpenAPI: cada signo admite `number` u objeto | Rutas, permisos, códigos de error existentes |
| Clientes | Opcional: enviar la unidad | `VitalSignApiClient` ya acepta cualquier cuerpo |

## Casos que defienden el cambio

| Entrada | Resultado esperado |
|---|---|
| `glucose: {5.5, mmol/L}` | Se normaliza a 99,1 mg/dL: sin alerta |
| `glucose: {2.9, mmol/L}` | 52,2 mg/dL: `critical` |
| `glucose: 5.5` (sin unidad) | Compatibilidad: 5,5 mg/dL, `critical`, como hoy |
| `temperature: {98.6, °F}` | 37,0 °C: 201 sin alerta |
| `temperature: {122, °F}` | 50 °C: 422 `VITAL_SIGN_IMPLAUSIBLE` |
| `glucose: {5.5, mg/L}` | 422 `UNIT_NOT_SUPPORTED` |
| `heart_rate: {80, mmol/L}` | 422 `UNIT_NOT_SUPPORTED` |

## Riesgos y decisiones

- **Pérdida del original.** Se guarda 99,1 mg/dL, no 5,5 mmol/L. Propuesta: el valor y la unidad originales van en la `metadata` de la auditoría H8 (sin texto clínico) cuando exista esa tabla.
- **Redondeo.** Se convierte con precisión completa y no se redondea antes de comparar: 2,95 mmol/L son 53,15 mg/dL y un redondeo anticipado podría saltar de banda. Solo se redondea al presentar.
- **Un número sin unidad sigue siendo canónico.** Es la compatibilidad que evita romper a los clientes actuales; el costo es que el error de unidad sigue posible para quien no la declare. Mitigación futura: exigir la unidad en `/api/v2`.
- **Factor de conversión.** 18,016 es la constante usual para glucosa; debe validarlo el responsable clínico junto con los umbrales.

## Cómo se defiende en la evaluación

1. **El problema es de frontera, no de umbrales.** Los umbrales están bien; lo que falla es qué número llega a ellos.
2. **No se toca lo que funciona.** Las reglas, el evaluador, el Repository y la base quedan igual: es la prueba de que OCP y la separación en capas se sostienen.
3. **Falla cerrada.** Una unidad desconocida se rechaza; nunca se adivina.
4. **Evidencia.** La demo ejecutable de la Semana 7 corre los siete casos de la tabla.
