# Evaluación de frontera de microservicio

## Criterios evaluados

| Criterio | Observación |
|---|---|
| Acoplamiento de datos | RBAC es dueño de roles, permisos y denegaciones; comparte con Usuarios/Auth solo la identidad. |
| Cadencia de cambio | El catálogo de permisos cambia poco; el contrato `v1` es estable. |
| Equipo responsable | RBAC pertenece al equipo del micro-HIS; sin equipo dedicado aún. |
| Latencia de `authorize` | Se llama en caliente en cada request; una llamada de red extra penalizaría. |
| Resiliencia | La decisión es síncrona; el registro de denegaciones tolera fallos (fail-safe). |

## Decisión razonada

RBAC se mantiene como **módulo lógico con contrato API estable**, no como microservicio físico todavía. Como todos los módulos lo llaman en caliente en cada request, la latencia de red extra penalizaría; el contrato API deja la puerta abierta a extraerlo luego sin cambiar clientes.

## Comunicación y consistencia

- La llamada `authorize` es **síncrona**: la decisión se necesita antes de responder.
- Las denegaciones se registran de forma **asíncrona tolerante a fallos** (fail-safe): si el registro falla, la decisión sigue siendo denegar.

## Migración futura

Extraer RBAC como microservicio requeriría, como mínimo:

- **Caché de permisos** para absorber la latencia de la llamada `authorize`.
- **Token con claims** (roles/permisos) para decidir sin volver a consultar el almacén.
- **Versionado del contrato** (`/api/v1`) para evolucionar sin romper a los clientes.

## Condición de salida

La migración solo se justifica con una **medición** (p95 de `authorize` y costo operativo); sin medición, se mantiene el monolito modular.

## Riesgos

| Riesgo | Mitigación |
|---|---|
| Proponer el microservicio sin medición | Criterios medibles y decisión razonada; monolito modular por defecto. |
| Latencia de red en el camino caliente | Caché de permisos y token con claims antes de extraer. |
| Contradecir códigos ya documentados | Reutilizar los nombres de acción y permisos de las semanas 1 a 4 sin redefinirlos. |

## Supuesto
Este análisis se reevalúa con mediciones de latencia y costo operativo.
