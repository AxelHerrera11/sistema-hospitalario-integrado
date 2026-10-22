# Semana 12 | ASII-19: Defensa de arquitectura y UX

## Recorrido

`Pendientes -> Captura -> Validación -> Corrección -> Auditoría`

El técnico captura; el sistema valida formato, unidad y rango; el bioquímico
autoriza la corrección. El estado pendiente no se pierde y cada cambio conserva
valor anterior, nuevo valor, motivo, usuario y fecha.

## Matriz decisión -> RF/RNF -> evidencia

| Decisión | Requisito | Evidencia |
|---|---|---|
| Separar captura de autorización | RF: solo rol autorizado corrige | Diagrama de flujo y Semana 9 |
| Mostrar unidad y rango | RNF: evitar error clínico | Checklist UX y Semana 10 |
| Conservar formulario ante error | RNF: recuperación sin duplicado | Prototipo y Semana 11 |
| Auditar cada corrección | RF: trazabilidad completa | Confirmación y registro de auditoría |
| Limitar por tenant/rol | RNF: mínimo privilegio | Reglas de interacción |

## Cambio solicitado y respuesta

**Cambio:** permitir corregir un valor fuera de rango sin perder el resultado
pendiente cuando la conexión falla.

**Respuesta:** la captura conserva valor, unidad y motivo; la validación marca
la alerta; el envío usa una clave idempotente y queda `En revisión` o `En cola`.
La autorización sigue siendo del bioquímico y el reintento no duplica auditoría.

## Criterios de defensa

- El camino feliz termina en estado trazable y muestra siguiente acción.
- El error recuperable conserva contexto sin modificar reglas clínicas.
- Desktop y móvil mantienen roles, estados, foco y etiquetas.
- No se usan datos reales; los resultados se consideran clínicamente sensibles.

## Evidencia

- Requisito Odoo: `Semana 12: Segunda evaluación parcial`.
- Entregable: recorrido navegable, matriz decisión→RF/RNF→evidencia y cambio respondido.
- La defensa conecta UML, UX, movilidad, accesibilidad y captura controlada.
