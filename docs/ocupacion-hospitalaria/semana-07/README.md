# Semana 7 - Componentes backend/frontend

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** diagrama de componentes, contratos de entrada/salida y comparación antes/después
  de un refactor concreto.

## Alcance

El diseño conecta el Micro-HIS existente con la interfaz Vue sin mover reglas de ocupación al
navegador. La tabla distingue lo **existente** (semanas 3 a 5, ya integrado) de lo **implementado
en la semana 16**; ninguna fila afirma una interfaz que no exista en `develop`.

- [Fuente editable del diagrama](./fuentes/01-componentes.mmd)
- [Declaración de IA](./DECLARACION_IA.md)

## Componentes y contratos

| Componente | Estado | Responsabilidad | Contrato principal |
|---|---|---|---|
| `OccupancyPage.vue` | Implementado (semana 16) | Componer indicadores, salas y estados visuales; la pantalla actual no incluye filtro de sala | Consulta mediante el cliente HTTP común; no calcula ocupación |
| `ocupacion.js` | Implementado (semana 16) | Utilidades puras: formateo, agregados y reglas de presentación | Sin `fetch`, sin estado global |
| `router/index.js` | Implementado (semana 16) | Publicar la ruta `/ocupacion` | Ruta y nombre de vista |
| Middleware `tenant` + `jwt.auth` + `rbac:ocupacion.ver` | Implementado (semana 16) | Autenticar, aislar por hospital y autorizar la lectura | Ruta protegida en `routes/api.php` |
| `OccupancyController` (Api/V1) | Implementado (semana 16) | Resolver el corte, invocar el caso de uso y elegir `200/503` | `GET /api/v1/occupancy?cutoff_at=` |
| `OcupacionServiceProvider` | Implementado (semana 16) | Cablear el Micro-HIS con el adaptador PDO y traducir el fallo de conexión | Conexión perezosa a la base HOSPITAL |
| `ConsultOccupancy` | Existente (semana 3) | Orquestar el puerto de lectura y el cálculo | `execute(cutoff)` |
| `OccupancyCalculator` | Existente (semana 3) | Clasificar cada cama y consolidar por sala | `calculate(beds, cutoff)` |
| `BedSnapshotReader` | Existente (semana 4) | Puerto de lectura de una fotografía temporal | `readAt(cutoff): list<BedSnapshot>` |
| `PdoBedSnapshotReader` | Existente (semana 4) | Leer con sentencia preparada y conexión perezosa | Implementa `BedSnapshotReader` |
| Doble en memoria | Existente (semana 3) | Ejecutar el caso de uso sin base de datos | Implementa `BedSnapshotReader` |

## Contratos de entrada y salida

| Componente | Entrada | Salida | Errores |
|---|---|---|---|
| Middleware | `Authorization: Bearer`, `X-Tenant-ID`, `X-Request-ID` | Petición autenticada con tenant resuelto | `401` sin token, `403` por tenant o permiso |
| `OccupancyController` | `GET /api/v1/occupancy` con `cutoff_at` opcional | `200` con `data` (corte, global, salas) y `meta.request_id` | `400` corte inválido, `405` método distinto de GET, `503` base no disponible |
| `OccupancySummaryQuery` | `\DateTimeImmutable $cutoff` | Arreglo con `cutoff_at`, `global` y `wards` | `PersistenceFailure` si la lectura falla |
| `BedSnapshotReader` | `\DateTimeImmutable $cutoff` | `list<BedSnapshot>` con cama, sala, estado y estancia activa | `PersistenceFailure` |
| `OccupancyCalculator` | Lista de instantáneas de camas | Tasas, distribución por sala, discrepancias y no verificadas | No lanza; la capacidad operativa cero produce tasa `null` |
| `OccupancyPage.vue` | Respuesta de la API o estado de error | Vista con estados distinguibles: carga, éxito, vacío, obsoleto, caído, denegado | Muestra `role="status"` y `role="alert"`; nunca inventa cifras |

## Dependencias permitidas

1. Vue depende del cliente HTTP común y del contrato de respuesta, nunca de SQL ni de entidades PDO.
2. `OccupancyPage.vue` usa `@/plugins/axios` directamente; un store de ocupación queda como propuesta, no como componente integrado.
3. Presentation depende de Application; Application depende del puerto y del dominio.
4. Persistence implementa el puerto del dominio; Domain y Application no dependen de PDO.
5. ASII-09 solo lee `wards`, `beds` y `bed_stays`; ASII-06/07/08 conservan sus escrituras.

## Comparación antes/después de un refactor concreto

**Refactor: resolución perezosa de la conexión HOSPITAL.** Es el punto de mayor acoplamiento entre
el módulo, el framework y la base: la conexión se obtenía al construir el adaptador, fuera del
alcance del manejo de fallos.

| Aspecto | Antes | Después |
|---|---|---|
| Momento de la conexión | Al resolver el binding en `OcupacionServiceProvider`, al construir `PdoBedSnapshotReader` | Dentro de `readAt()`, cuando la lectura realmente ocurre |
| Tipo del adaptador | Recibía un `\PDO` ya construido | Recibe `\PDO` **o** un `\Closure(): \PDO` (proveedor perezoso) |
| Fallo de base | Excepción de PDO fuera del `try/catch` del controlador: respuesta `500` con traza | `PersistenceFailure` traducida por el provider: respuesta `503` sin ocupación cero |
| Prueba que lo cubre | Ninguna: el fallo se daba por improbable | `tests/Feature/Ocupacion`: base HOSPITAL inexistente → `503` y nunca `500` |

**Justificación:** el módulo expone una lectura que puede fallar por infraestructura. Con la
conexión resuelta por adelantado, ese fallo se escapaba del contrato y devolvía `500`, que el
cliente no puede distinguir de un error del módulo. Con la resolución perezosa, el contrato de la
semana 5 se cumple: `503` con `error.code` y sin datos de ocupación.

**Efecto secundario aceptado:** la conexión se abre en la primera lectura y se reutiliza en las
siguientes, porque el proveedor devuelve la misma instancia mientras la aplicación viva. No se
implementa un pool propio: eso pertenece a la capa de infraestructura del grupo.

## Propuesta de refactorización pendiente

### R1 - Respuesta tipada

Reemplazar arrays internos por objetos de valor inmutables para el resumen y la ocupación por
sala. Criterio: ninguna clave HTTP aparece en el dominio y las verificaciones siguen pasando.

### R2 - Reloj explícito

Introducir un reloj en Application para evitar el reloj del sistema dentro del controller cuando
falta `cutoff_at`. Criterio: la prueba del corte actual usa un reloj fijo sin esperar tiempo real.

### R3 - Filtro por sala

Agregar `ward_id` opcional al objeto de consulta, validado por tenant. Criterio: una sala ajena
responde `404` y nunca mezcla hospitales.

### R4 - Observabilidad verificable

Extraer un puerto de métricas para duración, estado e inconsistencias, sin registrar
identificadores clínicos. Criterio: un doble prueba una métrica por solicitud y el fallo del
observador no altera la respuesta.

## Orden seguro

1. Introducir objetos de valor y reloj manteniendo el contrato v1.
2. Agregar filtro por sala y pruebas de tenant.
3. Añadir métricas y medir antes de reevaluar la frontera de microservicio.

Cada paso es reversible y no cambia la fuente autoritativa de ocupación.
