# Semana 6 — Primera evaluación parcial

**Módulo:** ASII-19 — Ingreso de resultados de laboratorio  
**Estudiante:** ORELLANA VÉLIZ, GERSON GIOVANNI

## Tesis arquitectónica

El flujo de captura y corrección controlada mantiene la trazabilidad desde los actores y UML hasta el dominio, las capas, Repository y el contrato HTTP. El resultado pendiente nunca se modifica sin validar tenant, unidad, estado y motivo de corrección.

## Integración de evidencias

| Decisión defendida | Evidencia | Criterio verificable |
|---|---|---|
| Separar reglas del transporte | src/Domain/PendingLabResult.php y ResultRuleValidator.php | Una entrada inválida no llega a Persistence |
| Aislar almacenamiento | PendingResultRepository y adaptadores InMemory/PDO | El caso de uso no contiene SQL |
| Conservar correcciones | historial de versión, valor anterior y motivo | La versión anterior puede auditarse |
| Proteger consistencia | pruebas de auditoría, persistencia y versión obsoleta | Un fallo deja el registro sin cambios |
| Exponer HTTP estable | public/index.php y controlador | El cuerpo y el código HTTP coinciden |

## Cambio práctico defendido

Si el laboratorio corrige un resultado de 95 mg/dL a 105 mg/dL, se valida la unidad esperada, se exige motivo, se crea la versión 2 y se conserva la versión 1 para auditoría. Si falla la auditoría o la persistencia, la versión 1 permanece activa.

## Validación

    php micro-his/ingreso-resultados-lab/tests/run.php
    OK: camino feliz, regla de dominio y error de persistencia verificados.

## Decisiones pendientes

La persistencia productiva del historial requiere una tabla de versiones/correcciones en PostgreSQL; el prototipo deja explícito ese límite sin simular infraestructura productiva.

