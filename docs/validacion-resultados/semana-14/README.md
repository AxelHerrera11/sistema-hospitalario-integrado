# Semana 14 - Plan SQA

**Modulo:** ASII-20 Validacion de resultados por bioquimico
**Tarea Odoo:** Semana 14 - Validacion de resultados por bioquimico

## Objetivo

Controlar calidad funcional, seguridad, consistencia y trazabilidad del flujo
de validacion sin usar datos clinicos reales.

## Metricas

| Metrica | Meta | Evidencia |
|---|---:|---|
| RF cubiertos por prueba | 100% | Matriz de casos |
| Errores con respuesta definida | 100% | Contrato API |
| Operaciones con auditoria | 100% | Consulta de AuditLog |
| Pruebas de permisos y tenant | 100% | Casos negativos |
| Datos reales en fixtures | 0 | Revision de fixtures |

## Estrategia

- Unitarias para decision, estado pendiente y motivo de rechazo.
- Feature para permisos, tenant y respuestas HTTP.
- Integracion para transaccion de estado mas auditoria.
- Regresion antes de solicitar merge.

## Criterio de salida

No quedan pruebas criticas rojas, toda evidencia identifica comando y resultado,
y los fallos de infraestructura se registran como pendientes, no como exitos.
