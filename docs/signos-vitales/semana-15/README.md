# Signos vitales y alertas por valores anormales — Semana 15: pruebas unitarias, de caja blanca y de caja negra

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales · **Entrega:** 59 pruebas trazadas a RF/RNF, comandos, resultados, capturas y 6 defectos registrados (2 corregidos).

## Ficha de la asignación

- **Semana:** Semana 15 — Pruebas unitarias, caja blanca y caja negra
- **Tarea individual:** Diseñe y ejecute pruebas de Signos vitales y alertas por valores anormales trazadas a RF/RNF: unitarias para reglas de signos vitales, unidades, umbrales, valores anormales y alertas clínicas; caja blanca con ramas/condiciones del flujo «captura de signos vitales, evaluación de umbrales y generación de alerta»; caja negra con equivalencia, límites, permisos y errores, sin probar módulos ajenos.
- **Entregable / evidencia:** matriz RF/RNF→prueba, mínimo 3 unitarias, 2 de caja blanca y 4 de caja negra, comandos/resultados, capturas y defectos registrados.
- **Issue:** Refs #23
- **Alcance:** pruebas nuevas en `micro-his/signos-vitales/tests/`. **No se corrige código**: los defectos nuevos se registran y se corrigen en PR aparte.

## Qué se prueba y qué no

Se prueban las clases del módulo (dominio, caso de uso `RecordVitalSigns`, `ApiKernel`) con **dobles en memoria** de lo ajeno: episodios y asignaciones (Admisión), RBAC y el canal de notificación. **No** se prueban esos módulos, PostgreSQL, el tenant real ni la interfaz. El rendimiento (RNF-SV-04) se mide en la [Semana 5](../semana-05/frontera-microservicio.md) con `bench.php` y no se repite aquí.

## Comandos y resultados

PHP 8.3.33, ejecutado el 2026-10-20 sobre la rama de la Semana 15 (corrección de P-2 y P-3 incluida).

```bash
cd micro-his/signos-vitales
php tests/unit.php        # 18 pruebas
php tests/whitebox.php    # 17 pruebas
php tests/blackbox.php    # 24 pruebas
php tests/run.php && php tests/repository.php && php tests/api.php && php tests/client.php   # regresión: 56
```

| Suite | Pruebas | OK | Fallos | Defectos abiertos | Captura |
|---|---|---|---|---|---|
| Unitarias (`unit.php`) | 18 | 17 | 0 | 1 | [unit](./capturas/unit.png) |
| Caja blanca (`whitebox.php`) | 17 | 17 | 0 | 0 | [whitebox](./capturas/whitebox.png) |
| Caja negra (`blackbox.php`) | 24 | 21 | 0 | 3 | [blackbox](./capturas/blackbox.png) |
| Regresión (`run.php`, `repository.php`, `api.php`, `client.php`) | 56 | 56 | 0 | 0 | [regresion](./capturas/regresion.png) |

Una prueba de defecto («ABIERTO») no rompe la suite; pasa a «OK» cuando el defecto se corrige. Un fallo sí termina con código 1.

## Diseño de las pruebas

- **Unitarias (U-01 a U-17, más la prueba del defecto D-06):** una clase por vez, sin adaptadores: `VitalSigns` (rangos y presión), `HeartRateRule`, `OxygenSaturationRule`, `GlucoseRule`, `NoAlertRule`, `ThresholdEvaluator`, `Assessment`, `Severity` y `Uuid`. U-13 fija la unidad de las reglas (mg/dL).
- **Caja blanca (W-01 a W-17):** `RecordVitalSigns::execute` se recorre en todas sus ramas (**12 de 12**: dominio, tres condiciones de la fecha, sin asignación, episodio cerrado, edad menor y mayor de 18, sin alerta, alerta con canal sano, canal caído con y sin manejador, y el orden de los pasos) y `ApiKernel::route` en sus **6 salidas** (fuera de `/api/v1`, sin identidad, ruta sin permiso, RBAC caído, sin permiso y permiso presente). Los espías cuentan las llamadas a `contextFor` y al canal para probar el orden y las omisiones.
- **Caja negra (B-01 a B-24):** solo por `ApiKernel`, sin mirar el código. Clases válidas e inválidas de signos (seis) y de `measured_at` (cinco más ausente y no textual), límites de plausibilidad y de las bandas de FC, glucosa, FR y SpO2, edad 17 y 18, permisos (401, 403 por rol, por asignación y por falta de permisos), falla segura, canal caído, `trace_id` y consulta.

## Matriz RF/RNF → prueba

| Requisito | Pruebas | Estado |
|---|---|---|
| RF-SV-01 · registrar solo el médico tratante, fecha válida | W-02 a W-06, W-12, B-01, B-02, B-04, B-05 | Cubierto |
| RF-SV-02 · rechazar lo imposible | U-01 a U-03, W-01, B-03, B-06, B-07 | Cubierto; **D-03** |
| RF-SV-03 · evaluar cada signo presente contra su umbral | U-04 a U-06, U-08, U-15 a U-17, B-08 a B-10, B-18, B-19, B-23, B-24 | Cubierto; **D-01** y **D-02** corregidos |
| RF-SV-04 · severidad máxima y alerta | U-09 a U-12, B-01 | Cubierto |
| RF-SV-05 · peso y talla sin alerta | U-07, B-02 | Cubierto |
| RF-SV-06 · sin perfil, guardar y avisar | W-07, B-11 | Cubierto |
| RF-SV-07 · auditar y notificar después del `COMMIT` | W-08 a W-11, B-14 a B-16, regresión (`run.php`) | Cubierto |
| RNF-SV-01 · seguridad | W-13 a W-17, B-12, B-13, B-17 | Parcial; **D-04**, **D-05**; la identidad por cabecera no se puede probar como defecto |
| RNF-SV-02 · seguridad del paciente (ninguna regla oculta otra alerta) | U-08, B-01 | Cubierto (D-01 corregido: ya todo signo del Anexo B tiene regla) |
| RNF-SV-03 · consistencia | W-10, B-14, B-15, regresión | Cubierto |
| RNF-SV-04 · rendimiento | — | **No probado aquí**: `bench.php` de la Semana 5 |
| RNF-SV-05 · mantenibilidad (LSP) | U-08 | Cubierto |
| Unidades (R3 y Semana 6) | U-13 | Parcial; **D-06** |

## Defectos registrados

| ID | Sev. | Prueba | Defecto | Corrección propuesta |
|---|---|---|---|---|
| **D-01** | Crítico | B-18 | **Temperatura, sistólica y diastólica no tienen regla de umbral**: 41 °C, 34 °C, 230/130 y 85 mmHg se guardan con `201 OK` y no generan alerta, aunque el Anexo B de la Semana 1 las define | **Corregido** ([umbrales](./umbrales/README.md)). Agregar tres subtipos de `RangeRule` con las bandas del Anexo B, validadas por el responsable clínico (riesgo R-01, métrica Q-06) |
| **D-02** | Mayor | B-19 | La glucosa de 53,5 mg/dL es `warning`; el Anexo B dice crítica para `< 54`, y la regla usa `≤ 53`. Con decimales (por ejemplo 2,95 mmol/L = 53,15 mg/dL) hay una banda mal clasificada | **Corregido** ([umbrales](./umbrales/README.md)). Cambiar el límite a `< 54` o declarar las bandas como enteras y rechazar decimales |
| **D-03** | Menor | B-20 | FC, FR y presión aceptan decimales (138,5), pero la columna es `integer` | Aceptar enteros en esos signos (decisión con Javier, riesgo R-10) |
| **D-04** | Menor | B-21 | Un id inexistente responde `404` y uno ajeno `403`: se pueden enumerar identificadores (P-4) | Autorizar el episodio antes de revelar si la medición existe |
| **D-05** | Crítico | B-22 | El módulo ignora `X-Tenant-ID`: una petición con un tenant ajeno se acepta (P-1, riesgo R-04) | Resolver el tenant en `ApiKernel` y acotar los repositorios, o documentar qué capa lo garantiza |
| **D-06** | Crítico | U-13 | No se puede declarar la unidad: glucosa 5,5 mmol/L se evalúa como mg/dL y da una alerta crítica falsa (P-7, riesgo R-02) | `Measurement` y `Unit` (Semana 7), con rechazo de la unidad desconocida |

Dos defectos de la revisión técnica ya están **corregidos** y sus pruebas son verdes: **P-2** (el canal caído tras el `COMMIT` ya no da `500`; W-10, W-11, B-15) y **P-3** (fecha futura; W-04, `run.php`). Los hallazgos nuevos D-01 y D-02 salen de esta semana: no estaban en los ocho puntos de atención de la revisión.

## Sensibilidad de las pruebas (mutaciones)

Se cambió una línea del código, se ejecutaron las tres suites y se restauró el archivo. De las diez mutaciones, **nueve se detectaron** y una, equivalente por construcción, sobrevive como control:

| Mutación | Resultado |
|---|---|
| `RangeRule`: crítico bajo con `<` en lugar de `<=` | 2 fallos |
| `RangeRule`: crítico alto con `>` en lugar de `>=` | 2 fallos |
| `VitalSigns`: mínimo inclusivo en lugar de exclusivo | 2 fallos |
| `ADULT_AGE` de 18 a 19 | 2 fallos |
| Episodio cerrado sin comprobar | 2 fallos |
| Sin comprobar la fecha futura | 1 fallo |
| `ApiKernel` sin capturar la caída de RBAC | error fatal |
| `ApiKernel` sin comprobar el permiso | 2 fallos |
| Glucosa: crítico alto en 401 | 2 fallos |
| `GetVitalSign` con una expresión equivalente (control) | 0 fallos: sobrevive, como debe |

## Archivos

- [Capturas](./capturas/) · [Declaración de uso de IA](./DECLARACION_IA.md)
- Pruebas: `micro-his/signos-vitales/tests/` (`support.php`, `unit.php`, `whitebox.php`, `blackbox.php`)

> Este PR añade pruebas sobre el código de la rama de correcciones de la Semana 13, apilada sobre los PR de las semanas 3 a 14, aún sin integrar en `develop`.
