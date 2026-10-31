# Corrección de D-01 y D-02: reglas de umbral que faltaban (Semana 15)

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales · **Entrega:** corrección de dos defectos registrados por las [pruebas de la Semana 15](../README.md#defectos-registrados).

## Aviso importante sobre estas cifras

Las bandas de este documento son las del Anexo B de la [Semana 1](../../semana-01/narrativa-alcance-trazabilidad.md): un **perfil de referencia académico, ilustrativo, contrastado con NEWS2 y ADA por el autor, que no es médico ni tiene formación clínica certificada**. No tiene revisión clínica profesional y **no es apto para uso asistencial**. Quien lo adopte para atender pacientes debe hacerlo revisar por personal clínico. La revisión clínica externa sigue pendiente (riesgo R-03 del [plan SQA](../../semana-14/riesgos-controles.md)).

## Qué se corrige

| Defecto | Antes | Ahora |
|---|---|---|
| **D-01** (Crítico) | Temperatura, sistólica y diastólica no tenían regla: 41 °C, 34 °C, 230/130 o 85 mmHg se guardaban con `201 OK` y **no generaban alerta** | Tres reglas nuevas, `TemperatureRule`, `SystolicBpRule` y `DiastolicBpRule`, subtipos de `RangeRule`, registradas en `app.php` |
| **D-02** (Mayor) | La glucosa de 53,5 mg/dL daba `warning`; el Anexo B dice crítica para `< 54` y la regla usaba `≤ 53` | `RangeRule` admite un crítico bajo exclusivo (`critLowExclusive`); `GlucoseRule` lo usa con 54: crítica con `< 54`, también con decimales |

El resto de las reglas no cambia: el parámetro nuevo de `RangeRule` es opcional y vale `false`, así que la frecuencia cardiaca, la respiratoria y la saturación se comportan igual.

## Bandas

`crítico bajo` se lee `≤` salvo en la glucosa (`<`). Las bandas son las del Anexo B.

| Signo | Unidad | Advertencia | Crítico | Regla |
|---|---|---|---|---|
| Temperatura | °C | 35,1–36,0 o 38,1–39,0 | ≤ 35,0 o ≥ 39,1 | `TemperatureRule` (nueva) |
| Sistólica | mmHg | 91–100 o 180–219 | ≤ 90 o ≥ 220 | `SystolicBpRule` (nueva) |
| Diastólica | mmHg | 110–119 | ≥ 120 | `DiastolicBpRule` (nueva, solo banda alta) |
| Glucosa | mg/dL | 54–69 o 181–399 | < 54 o ≥ 400 | `GlucoseRule` (límite corregido) |

## Contraste con NEWS2 (hecho por el autor, de memoria)

Se compararon las bandas con la tabla de NEWS2 del Royal College of Physicians (2017). **El autor no pudo consultar la fuente al escribirlo y no es clínico: quien lo revise debe confirmarlo contra la tabla oficial.**

| Signo | Coincidencia con NEWS2 | Nota |
|---|---|---|
| Temperatura | Coincide con las bandas de la escala | |
| Sistólica ≤ 90 y ≥ 220, 91–100 | Coincide | |
| **Sistólica 180–219** | **No viene de NEWS2** | Banda agregada en la Semana 1 |
| **Diastólica** | **No existe en NEWS2** | Banda agregada en la Semana 1; es la más débil del perfil y la que más conviene revisar |
| Glucosa | La ADA define la hipoglucemia nivel 2 por debajo de 54 mg/dL | Coincide con el crítico `< 54` |

## Verificación

```bash
cd micro-his/signos-vitales
php tests/unit.php && php tests/whitebox.php && php tests/blackbox.php
php tests/run.php && php tests/repository.php && php tests/api.php && php tests/client.php
php ../../docs/signos-vitales/semana-07/evidencia/demo-refactor.php
```

Todo termina con código de salida 0 (PHP 8.3.33). Pruebas nuevas o actualizadas:

| Prueba | Qué comprueba |
|---|---|
| U-15 | Bandas de temperatura en cada límite (35, 35,1, 36, 36,1, 38, 38,1, 39, 39,1) |
| U-16 | Bandas de sistólica (90 a 220) y diastólica (109 a 120) en sus límites |
| U-17 | Glucosa con decimales: 53,5 y 53,99 críticas; 54 warning |
| U-08 | Las **nueve** reglas no lanzan ni alertan ante una medición sin su signo |
| B-18, B-19 | Los antiguos defectos D-01 y D-02 pasan de «ABIERTO» a «OK» |
| B-23, B-24 | Los límites de temperatura, sistólica y diastólica por la API |
| B-06 | Actualizada: 25,1 °C y 45 °C son plausibles pero ahora críticos (antes no alertaban) |

Resultado: unitarias 18 pruebas (17 OK, 1 defecto abierto), caja blanca 17 (17 OK), caja negra 24 (21 OK, 3 defectos abiertos), regresión 56 (56 OK). Quedan abiertos D-03, D-04, D-05 y D-06.

**Sensibilidad (mutaciones).** Se cambió una línea, se ejecutaron las tres suites y se restauró el archivo. Las **seis mutaciones nuevas se detectaron**:

| Mutación | Resultado |
|---|---|
| `TemperatureRule`: crítico alto en 39,2 | 2 fallos |
| `SystolicBpRule`: crítico alto en 221 | 2 fallos |
| `DiastolicBpRule`: advertencia desde 111 | 2 fallos |
| `GlucoseRule`: crítico bajo inclusivo (`≤ 54`) | 2 fallos |
| `app.php` sin la regla de temperatura | 1 fallo |
| `app.php` sin las reglas de presión | 1 fallo |

Con ellas, de las 15 mutaciones aplicadas a lo largo de la Semana 15, 14 se detectan y una, equivalente por construcción, sobrevive como control. La mutación «Glucosa: crítico alto en 401» ya no aplica porque se cambió la línea de `GlucoseRule`.

Las capturas `unit.png` y `blackbox.png` de la Semana 15 se regeneraron con el resultado nuevo.

## Efectos y límites

- La API no cambia de forma: una medición con temperatura o presión anormales ahora devuelve `ALERT_GENERATED` y `findings` con ese signo, como ya hacía con los demás.
- Las bandas siguen siendo del perfil de adultos (18 años o más); los menores continúan con `THRESHOLDS_NOT_APPLICABLE`.
- Los valores entre bandas (por ejemplo, 36,05 °C) no están definidos en el Anexo B y se tratan como normales.
- La unidad sigue siendo la implícita (°C y mmHg); D-06 queda abierto.

## Archivos

- [Declaración de uso de IA](./DECLARACION_IA.md)
- Código: `micro-his/signos-vitales/src/Domain/` (`TemperatureRule.php`, `SystolicBpRule.php`, `DiastolicBpRule.php`, `RangeRule.php`, `GlucoseRule.php`), `app.php` y `tests/`.
