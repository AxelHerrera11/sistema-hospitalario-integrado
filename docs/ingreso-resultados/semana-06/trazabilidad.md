# Trazabilidad de la defensa

| Elemento | Evidencia de las semanas anteriores | Decisión defendida |
|---|---|---|
| Actores y permisos | Caso de uso y criterios de aceptación | El rol y el tenant se validan antes de corregir |
| Estado pendiente | Regla de dominio y flujo de actividad | Solo un resultado pendiente puede iniciar la corrección |
| Corrección controlada | Secuencia y Repository | Se exige motivo y se conserva el valor anterior |
| Persistencia | Adaptadores InMemory y PDO documentados | Un fallo no confirma una versión nueva |
| Integración HTTP | Contrato API de la semana 5 | La respuesta comunica validación, conflicto o error |

## Alcance de la evidencia

Esta entrega corresponde a la defensa documental de la evaluación parcial. La ejecución productiva del HIS, la identidad institucional y la tabla definitiva de historial quedan fuera de este PR y deben validarse durante la integración.
