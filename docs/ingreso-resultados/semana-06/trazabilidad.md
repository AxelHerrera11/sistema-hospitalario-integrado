# Trazabilidad de la defensa

| Elemento | Evidencia de las semanas anteriores | Decisión defendida |
|---|---|---|
| Actores y permisos | Caso de uso y criterios de aceptación | El rol y el tenant se validan antes de corregir |
| Estado pendiente | Regla de dominio y flujo de actividad | Solo un resultado pendiente puede iniciar la corrección |
| Corrección controlada | Secuencia y Repository | Se exige motivo y se conserva el valor anterior |
| Persistencia | Adaptadores InMemory y PDO documentados | Un fallo no confirma una versión nueva |
| Integración HTTP | Contrato API de la semana 5 | La respuesta comunica validación, conflicto o error |

## Trazabilidad de integración

| Dependencia | Responsabilidad de ASII-19 | Evidencia verificable |
|---|---|---|
| ASII-17 — Catálogo de pruebas | Consumir el `lab_test_id` de la versión exacta y respetar unidad, rango y umbral crítico | El resultado no depende de una copia local del nombre o rango |
| ASII-21 — Alertas críticas | Publicar `result-validated` solo después de validar; permitir reintentos idempotentes | `result_version_id` identifica el evento y evita alertas duplicadas |
| Convención transversal | Enviar tenant, usuario y request id; devolver errores problem+json | `X-Tenant-ID`, `X-User-Ref`, `X-Request-Id` y `code` estable |

ASII-19 no es dueño de las tablas `lab_tests` ni `critical_alerts`. La defensa
usa el contrato compartido como límite entre módulos y deja la ejecución
productiva sujeta a la integración de sus PR respectivos.

## Alcance de la evidencia

Esta entrega corresponde a la defensa documental de la evaluación parcial. La ejecución productiva del HIS, la identidad institucional y la tabla definitiva de historial quedan fuera de este PR y deben validarse durante la integración.
