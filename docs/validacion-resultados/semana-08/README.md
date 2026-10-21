# Semana 8 | ASII-20: UX de validación bioquímica

## Objetivo

Diseñar el flujo UX de revisión, aprobación y rechazo de resultados por bioquímico.

## Flujo por rol

1. El bioquímico filtra resultados pendientes por unidad, prioridad o paciente.
2. La pantalla muestra valor, unidad, rango, historial y alertas sin ocultar contexto clínico.
3. El bioquímico aprueba o rechaza; el rechazo exige motivo claro.
4. El sistema confirma la decisión y notifica al técnico cuando requiere corrección.

## Estados, errores y ayudas

| Estado | Mensaje o ayuda |
|---|---|
| Pendiente | Resaltar datos fuera de rango y permitir abrir historial. |
| En revisión | Avisar que otro usuario está trabajando el resultado. |
| Aprobado | Confirmar fecha, usuario y siguiente estado. |
| Rechazado | Solicitar motivo y mostrarlo al técnico. |
| Error de conexión | Mantener decisión sin duplicarla y ofrecer reintento. |

## Wireframe inicial

```text
[Bandeja de validación] [Unidad] [Prioridad] [Buscar]
Paciente | Prueba | Valor | Rango | Alertas | Estado
------------------------------------------------------
[Abrir resultado]

[Detalle de validación]
Resultado: [105 mg/dL] Historial: [Ver]
Comentario: [                         ]
[Aprobar] [Rechazar]
```

Anotación: el bioquímico solo ve resultados de su tenant y unidad autorizada; valor, unidad, rango, firma y trazabilidad permanecen disponibles.

### 2. Estado vacío y ayuda contextual

```text
[Bandeja de validación]
No hay resultados para revisar.
[Limpiar filtros] [Actualizar]
Ayuda: use prioridad y rango para ordenar la revisión.
```

Anotación: el estado vacío no expone datos clínicos fuera del alcance y explica cómo continuar.

### 3. Carga y revisión

```text
[Cargando resultado...]
Resultado: ******  Rango: ******
Historial y firma: [cargando]
[Cancelar]
```

Anotación: durante la carga se enmascaran datos sensibles y se bloquean decisiones hasta recuperar el contexto completo.

### 4. Decisión y firma

```text
[Confirmar validación]
Resultado: [105 mg/dL] Unidad: [mg/dL]
Firma del bioquímico: [                 ]
Comentario: [                         ]
[Cancelar] [Aprobar] [Rechazar]
```

Anotación: aprobar o rechazar exige revisar rango, comentario y firma; la acción queda asociada al usuario autorizado.

### 5. Éxito y error recuperable

```text
[APROBADO] Resultado trazable y notificado al técnico.
[Ver auditoría] [Siguiente resultado]

[ERROR RECUPERABLE] No se pudo registrar la decisión.
La revisión permanece pendiente.
[Reintentar] [Ver causa]
```

Anotación: el éxito confirma estado, firma y trazabilidad; el error conserva la revisión sin duplicar la decisión.

## Reglas de interacción

- Aprobar requiere revisar valor, unidad y rango.
- Rechazar exige motivo y confirmación antes de cambiar el estado.
- Una decisión bloquea botones mientras se procesa para evitar duplicados.
- Los errores de red conservan el formulario y permiten reintentar.
- Se protegen resultados, revisión, rechazo y firma mediante tenant, unidad, rol y mínimo privilegio.
- La trazabilidad registra usuario, fecha, decisión, motivo y estado anterior/nuevo.

## Evidencia

- Flujo UX: `fuentes/01-user-flow.mmd`.
- Cinco wireframes anotados con vacío, carga, éxito y error recuperable.
- Issue relacionado: `Refs #27`.
- PR menor de 400 líneas.
