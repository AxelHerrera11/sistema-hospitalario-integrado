# Narrativa de alcance y trazabilidad - Dashboard de ocupación hospitalaria

## Propósito

Permitir que un usuario autorizado conozca la ocupación del hospital por sala y estado de cama a partir de una fotografía consistente de los datos de la base HOSPITAL. El dashboard apoya la coordinación operativa; no asigna, traslada ni libera camas.

## Objetivo del proceso

Consolidar las salas y sus camas, derivar la ocupación clínica en una fecha de corte y presentar totales y porcentajes por sala. El resultado debe identificar discrepancias entre la ocupación derivada y el estado operativo del catálogo para evitar cifras engañosas.

## Actores

| Actor | Responsabilidad dentro del alcance |
|---|---|
| Usuario autorizado | Consulta el resumen y aplica un filtro opcional de sala. Es una abstracción; los roles y permisos concretos se confirmarán con RBAC. |
| Coordinación de camas | Utiliza la ocupación agregada para coordinar capacidad y detectar datos inconsistentes. |
| Dirección hospitalaria | Consulta indicadores agregados para supervisión operativa. |

Los nombres de los actores describen responsabilidades funcionales, no crean todavía roles técnicos. La autorización definitiva se diseñará con RBAC. ASII-06, ASII-07 y ASII-08 son propietarios funcionales de los datos consultados, pero no se modelan como actores porque el flujo propuesto lee las tablas locales de la base HOSPITAL y aún no existe un contrato de interacción entre módulos.

## Alcance funcional

### Incluido

- Consultar el total de camas de las salas seleccionadas.
- Derivar camas ocupadas mediante el intervalo de `bed_stays` activo en la fecha de corte.
- Clasificar camas sin estancia activa por su estado operativo: `available`, `maintenance`, `inactive` o `occupied` no verificado.
- Consolidar cantidades y porcentajes por sala y para el hospital consultado.
- Filtrar por una sala válida cuando el usuario lo solicite.
- Informar la fecha y hora de corte del resumen.
- Detectar discrepancias entre estancia activa en la fecha de corte y `beds.status`.
- Responder sin datos clínicos identificables del paciente.

### Fuera de alcance

- Crear o editar salas y camas.
- Admitir, trasladar o dar de alta pacientes.
- Asignar o liberar camas desde el dashboard.
- Mostrar nombres, diagnósticos u otros datos clínicos de pacientes.
- Definir todavía el contrato HTTP definitivo; corresponde a la Semana 5.
- Sustituir los módulos ASII-06, ASII-07 o ASII-08 como propietarios de sus datos.

## Regla de consolidación

El esquema permite `beds.status` con valores `available`, `occupied`, `maintenance` e `inactive`, pero documenta ese campo como no autoritativo para ocupación. Para una fecha de corte `T`, una estancia está activa cuando `started_at <= T` y (`ended_at IS NULL` o `ended_at > T`). Por ello:

1. Una cama se cuenta como **ocupada** únicamente si tiene una estancia activa en `T`.
2. Sin estancia activa, `available`, `maintenance` e `inactive` determinan su clasificación operativa.
3. `status = occupied` sin estancia activa no se cuenta como ocupación clínica; se clasifica como **no verificada** y se reporta como inconsistencia.
4. Una estancia activa con estado distinto de `occupied` se cuenta como ocupada y también se reporta como inconsistencia.
5. Cada cama se contabiliza una sola vez en el total de su sala.

La restricción de exclusión de PostgreSQL evita dos estancias temporales superpuestas para la misma cama, pero el dashboard debe manejar de forma segura cualquier error de lectura o dato incoherente.

### Definición de indicadores

- **Total de inventario:** todas las camas de las salas incluidas por el filtro.
- **Distribución por clasificación:** cantidad de cada categoría dividida entre el total de inventario. Si no hay camas, todas las cantidades y porcentajes de distribución son cero.
- **Capacidad operativa para ocupación:** camas ocupadas derivadas más camas disponibles sin estancia activa. Excluye mantenimiento, inactivas y no verificadas.
- **Tasa de ocupación:** `ocupadas / (ocupadas + disponibles) * 100`, redondeada a dos decimales para presentación.
- **Sin capacidad operativa:** si el denominador es cero, la tasa se devuelve como no aplicable, no como cero.
- **Inconsistencias:** contador adicional; una cama ocupada puede también incrementar este contador sin duplicarse en el inventario.

## Flujo principal

1. El usuario abre el dashboard y opcionalmente selecciona una sala.
2. El sistema valida autenticación, hospital activo, permiso y filtros.
3. El sistema fija una fecha de corte y el repositorio de lectura obtiene las salas, camas e intervalos de estancia de la base HOSPITAL activa.
4. El agregador recorre las camas, deriva ocupación, clasifica estados operativos y detecta discrepancias.
5. El sistema calcula totales y porcentajes por sala y globales.
6. La interfaz presenta el resumen, la fecha de corte y la cantidad de inconsistencias sin exponer información del paciente.

## Excepciones y resultados alternos

| Condición | Resultado esperado |
|---|---|
| Sesión ausente o token inválido | No se consulta ni muestra ocupación. Referencia preliminar: `401`. |
| Hospital activo ausente o desconocido | No se mezclan datos de hospitales. Referencia preliminar: `400/404`. |
| Usuario sin permiso | No se exponen cifras. Referencia preliminar: `403`. |
| Sala o filtros inválidos | Errores identificables por campo. Referencia preliminar: `422`. |
| Base HOSPITAL no disponible | No se presentan cifras parciales como si fueran completas. Referencia preliminar: `503`. |
| No hay salas o camas | Respuesta exitosa con estado vacío y fecha de corte. |
| Estado operativo e intervalo activo discrepan | La cama se clasifica según la regla autoritativa y se incrementa el contador de inconsistencias. |

## Precondiciones y poscondiciones

### Precondiciones

- El usuario tiene una sesión válida y un hospital activo.
- El usuario posee el permiso de consulta que se defina para ASII-09.
- Las migraciones HOSPITAL de salas, camas y estancias están disponibles.

### Poscondiciones

- No se modifica ninguna tabla clínica ni operativa.
- El usuario recibe un resumen del hospital activo con fecha de corte.
- Las inconsistencias detectadas se muestran como indicador operativo y no se corrigen automáticamente.

## Trazabilidad entre diagramas

| Caso de uso | Objetivo | Actividad | Secuencia |
|---|---|---|---|
| UC-OCC-01 Consultar ocupación | Obtener el resumen autorizado. | Solicitud inicial, validaciones y resultado exitoso. | `Solicitar resumen de ocupación`, `consultar` y `Mostrar ocupación`. |
| UC-OCC-02 Consolidar por sala | Calcular cantidades y porcentajes sin duplicar camas. | Fecha de corte, consulta, recorrido opcional y consolidación por sala. | `consolidar`, `obtenerInventarioEnFecha`, bucle de cero o más camas y agregación. |
| UC-OCC-03 Clasificar estados | Separar ocupada, disponible, mantenimiento, inactiva y no verificada. | Decisiones “¿Estancia activa en la fecha de corte?” y “¿beds.status?”. | Operaciones internas de evaluación temporal y clasificación dentro del bucle. |
| UC-OCC-04 Filtrar por sala | Limitar el resumen a una sala solicitada. | Validación de filtros antes de consultar. | `consultar(filtros)` y alternativa `422`. |
| UC-OCC-05 Mostrar inconsistencias | Evitar ocultar discrepancias de origen. | Ramas de estado incompatible y contador final. | `detectar discrepancias` y respuesta con inconsistencias. |
| UC-OCC-06 Validar acceso | Proteger cifras por sesión, hospital y permiso. | Decisiones iniciales con `401`, `400/404` y `403`. | Alternativas en autenticación/hospital/permiso. |

## Dependencias y decisiones pendientes

- ASII-06 debe confirmar el significado operativo y las transiciones permitidas de `beds.status`.
- ASII-07/08 deben conservar correctamente la apertura y finalización de `bed_stays`.
- RBAC debe definir el permiso y los roles concretos que consultarán el dashboard.
- En Semana 2 se formalizarán RF/RNF, criterios de aceptación y segregación de interfaces mediante ISP.
- En Semana 5 se decidirán el endpoint, payload, errores y estrategia de actualización o caché.

Los códigos HTTP de los diagramas son referencias ilustrativas para hacer explícitas las respuestas solicitadas por el ejercicio. No constituyen todavía el contrato API definitivo, que corresponde a la Semana 5.

## Fuentes del repositorio

- [`README.md`](../../../README.md), asignación ASII-09 y matriz de ejercicios individuales.
- [Migración de agenda, salas, camas y estancias](../../../database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php).
- [Pruebas de integridad de agenda y camas](../../../tests/Database/Hospital/SchedulingAndBedIntegrityTest.php).
- [Evidencia de aplicación del esquema](../../../openspec/changes/postgresql-federated-schema-rebuild/apply-progress.md), incluida la decisión de que `beds.status` no es autoritativo para ocupación.
