# Semana 8 | ASII-19: UX de ingreso de resultados

## Objetivo

Diseñar el flujo UX para capturar y corregir de forma controlada resultados pendientes.

## Flujo por rol

1. El técnico filtra resultados pendientes por paciente, muestra unidad y rango, y captura el valor.
2. El sistema valida formato, rango y estado antes de habilitar el guardado.
3. El bioquímico revisa el resultado y autoriza o rechaza la corrección.
4. El sistema confirma el nuevo estado y muestra la trazabilidad.

## Estados, errores y ayudas

| Estado | Mensaje o ayuda |
|---|---|
| Pendiente | Mostrar paciente, prueba, unidad y rango esperado. |
| Valor inválido | Indicar formato y rango sin borrar lo capturado. |
| Corrección en revisión | Informar quién debe autorizar y bloquear duplicados. |
| Rechazado | Mostrar motivo y permitir corregir solo al rol autorizado. |
| Guardado | Confirmar estado y enlace a la auditoría. |

## Wireframe inicial

```text
[Resultados pendientes] [Buscar] [Filtro estado]
Paciente | Prueba | Valor | Unidad | Rango | Estado
------------------------------------------------------
[Editar] [Ver historial]

[Capturar o corregir]
Valor: [          ] Unidad: [mg/dL]
Motivo: [                         ]
[Validar] [Enviar a revisión]
```

## Reglas de interacción

- El botón de envío permanece deshabilitado si falta valor, unidad o motivo.
- Los errores aparecen junto al campo y se mantienen hasta corregirse.
- La corrección exige confirmación y muestra el valor anterior.
- El historial es de solo lectura para el técnico.

## Evidencia

- Flujo UX: `fuentes/01-user-flow.mmd`.
- Issue relacionado: `Refs #26`.
- PR menor de 400 líneas.
