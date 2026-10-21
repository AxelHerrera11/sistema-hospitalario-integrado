# Refactor: antes y después

## Problema y evidencia

`PatientController` era el componente de Presentation con más responsabilidades sobre las fronteras: coordinaba las operaciones y su método privado `respond()` conocía `PatientOperationFailure` y `PersistenceFailure`, asignaba `404/422/500` y elegía el envelope mediante `PatientView`. Por tanto, cada nuevo formato o regla de traducción exigía tocar el Controller además de coordinar el caso de uso.

## Antes

`Controller → PatientService` y `Controller → PatientView`. El Controller ejecutaba callbacks, capturaba fallos de Application y decidía la respuesta; `PatientView` solo serializaba pacientes y errores.

## Decisión y después

Se extrajo esa traducción a `PatientResponsePresenter`, dentro de Presentation. El Controller ahora delega las operaciones y pasa el estado de éxito previsto; el presentador traduce únicamente los fallos de Application ya definidos y reutiliza `PatientView` para la salida. Antes, el Controller conocía directamente `PatientService` y `PatientView`; después, delega la salida al presentador: `Controller → PatientResponsePresenter → PatientView`.

`PatientResponsePresenter` es el límite entre los resultados/fallos de Application y el envelope de Presentation. No cambia el contrato de respuesta, las excepciones ni las reglas. Los fallos desconocidos siguen propagándose: el presentador no incorpora una captura genérica ni una respuesta exitosa de reserva.

## Beneficio e impacto en pruebas

El Controller ya no conoce las clases de excepción de Application ni implementa mapeo de errores. El comportamiento observable se mantiene bajo la suite existente: alta `201`, lectura/actualización `200`, duplicado `422`, regla de dominio y persistencia `500`. La prueba usa el flujo Controller–Service–Presenter y un repositorio en memoria o double de fallo.

## Deliberadamente no refactorizado

No se alteraron `PatientService`, Domain, contratos API/OpenAPI, esquemas o migraciones, adaptadores PDO, wiring HTTP, middleware transversal ni otros módulos. No se añadió una capa HTTP ni frontend, dado que no existen en la integración actual de Pacientes.
