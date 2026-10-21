# Semana 14 Plan SQA

**Modulo:** ASII-19 Ingreso de resultados de laboratorio

## Objetivo

Definir un plan medible para verificar captura, correccion controlada, versionado, permisos, tenant, errores y auditoria sin usar datos clinicos reales.

## Metricas

| Metrica | Meta | Evidencia |
|---|---:|---|
| Criterios funcionales cubiertos | 100% | Matriz RF y pruebas |
| Casos de error con respuesta | 100% | Tabla de errores |
| Rutas con permiso documentado | 100% | Contrato API |
| Datos reales en fixtures | 0 | Revision de fixtures |

## Estrategia

Se combinan pruebas unitarias de reglas, pruebas de contrato de respuestas y una prueba de integracion con repositorio controlado. Los fallos deben indicar causa, estado final y accion de recuperacion.
