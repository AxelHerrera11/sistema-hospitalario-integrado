# ¿Microservicio independiente para el catálogo?

**Decisión:** no separar físicamente ahora. El catálogo queda como módulo del monolito Laravel con una frontera lógica: contratos segregados, Repository y API v1. Se reevaluará si se cumple algún disparador medible.

## Criterios evaluados

| Criterio | Situación actual | ¿Justifica separar? |
|---|---|---|
| Dependencias de datos | `medication` y `prescription` comparten base HOSPITAL (slice H5); se propone una FK `prescription.medication_id` | No: separar rompería esa integridad y convertiría lecturas locales en llamadas de red |
| Consumidores | Prescripciones y Alergias, ambos dentro del mismo monolito | No: una llamada en proceso basta |
| Escala | Catálogo de cientos o miles de filas por hospital, escrito por pocos gestores y muy leído | No hay presión de escala medida |
| Despliegue independiente | No hay equipo ni ciclo de liberación propio para el catálogo | No |
| Seguridad | Ya cubierta por tenant, JWT y RBAC del monolito | Separar duplicaría la validación de identidad |
| Costo operativo | Nuevo servicio, red, observabilidad distribuida y versionado de contrato | En contra |

## Disparadores medibles para reconsiderar

Se revisa la decisión si se cumple al menos uno durante 30 días seguidos en un entorno real:

1. p95 de `GET /api/v1/medications` por encima de 300 ms por causa del catálogo, no de la red ni de la base compartida.
2. Más de un sistema externo al HIS que necesite el catálogo por HTTP.
3. Un ciclo de liberación del catálogo que deba ser independiente del monolito.
4. Necesidad de un catálogo común entre hospitales, que hoy no existe y requeriría la base CENTRAL.

Estos umbrales son **propuestos**: hoy no hay mediciones porque no existe un entorno desplegado.

## Migración razonada (si se dispara)

| Paso | Acción | Señal de éxito |
|---|---|---|
| 1 | Mantener el contrato `/api/v1/medications` como frontera única | Ningún consumidor accede a la tabla directamente |
| 2 | Hacer que Prescripciones lea por contrato (`MedicationLookup` o HTTP), no por SQL | Sus pruebas pasan con un doble del catálogo |
| 3 | Sustituir la FK por `medication_id` lógico y validación al prescribir | Integridad verificada por el caso de uso, con prueba de medicamento inactivo |
| 4 | Mover Application y Domain del catálogo a un servicio con su base | `tests/repository.php` y el contrato OpenAPI siguen pasando |
| 5 | Agregar reintento solo en lecturas, timeout y circuit breaker en el cliente | Un fallo del servicio da 503 explícito, nunca datos vacíos |

El orden evita el error inverso: extraer la tabla antes de migrar a sus consumidores.
