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

Anotación: el técnico solo ve resultados de su tenant y rol; unidad, rango, valor anterior y estado pendiente permanecen visibles.

### 2. Estado vacío y ayuda contextual

```text
[Pendientes]
No hay resultados pendientes para este filtro.
[Limpiar filtros] [Actualizar]
Ayuda: revise paciente, prueba y unidad antes de capturar.
```

Anotación: no se muestran datos clínicos de otros filtros o tenants y se ofrece una acción clara para continuar.

### 3. Carga y validación

```text
[Cargando reglas...]
Valor: [105] Unidad: [mg/dL] Rango: [70-100]
[Validando...]   [Cancelar]
```

Anotación: mientras carga la regla se bloquea el envío duplicado; el mensaje contextual explica formato, unidad y rango esperado.

### 4. Corrección y confirmación

```text
[Corrección controlada]
Anterior: [95 mg/dL] Nuevo: [105 mg/dL]
Motivo: [                         ]
[Cancelar] [Confirmar y enviar]
```

Anotación: el valor anterior no se puede editar, el motivo es obligatorio y la confirmación advierte que habrá trazabilidad.

### 5. Éxito y error recuperable

```text
[EN REVISIÓN] Corrección enviada al bioquímico.
[Ver auditoría] [Volver a pendientes]

[ERROR RECUPERABLE] No se pudo enviar.
El valor permanece guardado localmente.
[Reintentar] [Ver causa]
```

Anotación: el éxito muestra el estado y la auditoría; el error conserva valor, unidad y motivo sin duplicar la solicitud.

## Reglas de interacción

- El botón de envío permanece deshabilitado si falta valor, unidad o motivo.
- Los errores aparecen junto al campo y se mantienen hasta corregirse.
- La corrección exige confirmación y muestra el valor anterior.
- El historial es de solo lectura para el técnico.
- Se protegen resultados, valores, unidades, rangos y motivos mediante tenant, rol y mínimo privilegio.
- La trazabilidad registra valor anterior, nuevo valor, usuario, fecha y estado pendiente.

## Evidencia

- Flujo UX: `fuentes/01-user-flow.mmd`.
- Cinco wireframes anotados con vacío, carga, éxito y error recuperable.
- Issue relacionado: `Refs #26`.
- PR menor de 400 líneas.
