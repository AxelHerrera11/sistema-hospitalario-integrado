# Narrativa de alcance y trazabilidad

**Módulo:** `ASII-23` | **Issue:** `#152` | **PR futuro:** hacia `develop` con `Refs #152`.

## Alcance y carácter propuesto

`UC-RA-01` permite que un **usuario autorizado** solicite, calcule y visualice de forma trazable un único indicador del hospital/tenant activo. La asignación pide modelar un indicador, pero no selecciona uno ni establece su fórmula. Por ello, `IND-RA-01`, su nombre **tasa de cancelación de hospitalizaciones** y `TCI-1.0` son una propuesta exclusiva para esta tarea, no una definición clínica oficial ni un requisito explícito del docente. No se consultan nombres, identificadores de pacientes ni otros datos personales.

## Definición de `IND-RA-01`

| Elemento | Definición |
|---|---|
| Fórmula `TCI-1.0` | `100 × N_cancelados / N_finalizados` |
| Numerador | `N_cancelados = |C|`, donde `C` es el subconjunto de `F` con `status = cancelled` |
| Denominador | `N_finalizados = |F|` |
| Unidad | Porcentaje, mostrado con dos decimales |
| Periodo | `[desde, hasta)` aplicado a `ended_at`; ambos límites son instantes con zona/offset explícito y `desde < hasta` |
| Filtros | Hospital/tenant activo obligatorio; `service_code` exacto y opcional |
| Fuente | Base HOSPITAL, tabla `care_episodes`: `type`, `status`, `started_at`, `ended_at`, `service_code` |

## Conjuntos exactos

El esquema admite `status IN (open, closed, cancelled)` y `type IN (outpatient, emergency, inpatient)`. Para esta propuesta, no como equivalencia clínica general, se consideran finalizadas únicamente las filas del conjunto:

`F = {e ∈ care_episodes | e.type = inpatient ∧ e.status ∈ {closed, cancelled} ∧ desde <= e.ended_at < hasta ∧ (sin filtro de servicio ∨ e.service_code = servicio)}`.

El conjunto cancelado es `C = {e ∈ F | e.status = cancelled}`; por construcción, `C ⊆ F`. `open` queda fuera, al igual que `outpatient` y `emergency`. Si se proporciona `service_code`, una fila con valor nulo o diferente no coincide; sin ese filtro, `service_code` nulo no implica incompletitud.

`ended_at` es `timestamptz`. Como el repositorio no define una zona horaria hospitalaria para este indicador, `desde` y `hasta` deben incluir zona u offset y se comparan como instantes; no se presupone una zona local. El límite inferior es inclusivo y el superior exclusivo.

## Detección acotada de datos incompletos

Una sola lectura agregada propuesta devuelve `N_finalizados` y `N_cancelados`. Se informa **lectura incompleta o incoherente** únicamente si falta uno de esos conteos, no es un entero no negativo o incumple `N_cancelados <= N_finalizados`. No se calcula ni se muestra un porcentaje en ese estado; se muestra `IND-RA-01`, `TCI-1.0`, periodo, filtros, fuente prevista y fecha del intento.

La consulta del periodo no puede detectar una fila con `ended_at IS NULL`: la condición `desde <= ended_at < hasta` la excluye y no permite atribuirla al periodo. No se promete esa detección. El esquema actual reduce ese riesgo porque exige `ended_at > started_at` para `closed` y `cancelled`, mientras `open` exige `ended_at IS NULL`; una revisión de datos corruptos fuera del filtro sería otro proceso, fuera de este alcance.

## Precondiciones y flujo

1. La identidad está autenticada y el contexto corresponde al hospital/tenant activo.
2. El sistema propuesto autoriza consultar analytics solo dentro de ese hospital.
3. El usuario indica un periodo válido y, opcionalmente, un `service_code`.
4. Una lectura agregada obtiene `N_finalizados` y `N_cancelados` de `F` y `C`, sin seleccionar datos personales.
5. Se valida que ambos conteos estén presentes, sean enteros no negativos y cumplan `N_cancelados <= N_finalizados`.
6. Si la lectura es incoherente o el denominador es cero, se prepara un estado sin porcentaje con indicador, versión, periodo, filtros, fuente prevista y fecha del intento.
7. Con lectura coherente y denominador mayor que cero se calcula el porcentaje.
8. Se muestra el estado o valor trazable; solo un cálculo completado presenta porcentaje y fecha de cálculo.

## Resultados y excepciones

- **Cero válido:** si `N_finalizados > 0` y `N_cancelados = 0`, el resultado exitoso es `0.00 %`.
- **Ausencia de datos / denominador cero:** si `N_finalizados = 0`, se informa “sin datos para el periodo” sin porcentaje, junto con la trazabilidad del intento.
- **Lectura incompleta o incoherente:** falta un conteo, no es entero no negativo o `N_cancelados > N_finalizados`; se informa el estado sin porcentaje y con la trazabilidad del intento.
- **Acceso o alcance inválido:** se rechaza antes de consultar HOSPITAL y sin divulgar información hospitalaria no autorizada.
- **Parámetros inválidos:** se solicita corregir periodo o filtro y no se consulta.
- **Error de consulta o cálculo:** se presenta la fase que falló, sin atribuir una lectura exitosa ni un cálculo completado y sin mostrar cifras parciales.

## Existente y propuesto

| Componente o regla | Estado |
|---|---|
| Conexión PostgreSQL `hospital` y tabla `care_episodes` | **Existente** en `config/database.php` y migración HOSPITAL `2026_08_11_000200` |
| Tipos, estados y regla `ended_at > started_at` de episodios finalizados | **Existentes** en la misma migración |
| `IND-RA-01`, conjuntos `F`/`C` y fórmula `TCI-1.0` | **Propuestos para esta tarea**; no son definición clínica oficial ni requisito expreso |
| Cabecera tenant y comprobación JWT/tenant | **Scaffold existente**, actualmente afectado por los modelos de autenticación pendientes indicados en el README raíz |
| Autorización específica de analytics, consulta agregada, cálculo y visualización | **Propuestos**; no están implementados |
| Persistencia o auditoría del resultado | **Fuera de alcance**; no se garantiza ni se inventa |

## Trazabilidad

| Condición de `UC-RA-01` | Casos de uso | Actividad | Secuencia |
|---|---|---|---|
| Autorizar y limitar al hospital activo | `Validar acceso y alcance` | Decisión de acceso | Alternativa de acceso rechazado |
| Validar periodo y servicio | `Validar parámetros` | Decisión de parámetros | Autovalidación y alternativa inválida |
| Leer una fuente consistente | `Calcular IND-RA-01` | Una lectura agregada | Mensaje único a `care_episodes` |
| Cero válido | Nota del cálculo | Rama `cancelados = 0` | Alternativa `0.00 % válido` |
| Sin datos / denominador cero | Extensión `Informar sin datos` | Estado + trazabilidad del intento | Estado sin porcentaje + metadatos |
| Lectura incompleta o incoherente | Extensión de lectura incompleta | Estado + trazabilidad del intento | Conteos inválidos, sin porcentaje + metadatos |
| Error sin éxito | Extensión `Informar error` | Rama de fallo | Mensaje de error, sin valor |
| Estado o resultado trazable | `Visualizar estado o resultado` | Preparar metadatos | Respuesta con estado o valor y su trazabilidad |

## Exclusiones

No se definen RF/RNF, SOLID, backend, endpoints ni interfaz implementada. Tampoco se crean integraciones, almacenamiento de resultados, exportaciones, comparaciones entre hospitales o garantías de auditoría/persistencia.
