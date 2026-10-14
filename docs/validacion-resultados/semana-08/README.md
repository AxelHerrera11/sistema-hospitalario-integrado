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

## Reglas de interacción

- Aprobar requiere revisar valor, unidad y rango.
- Rechazar exige motivo y confirmación antes de cambiar el estado.
- Una decisión bloquea botones mientras se procesa para evitar duplicados.
- Los errores de red conservan el formulario y permiten reintentar.

## Evidencia

- Flujo UX: `fuentes/01-user-flow.mmd`.
- Issue relacionado: `Refs #27`.
- PR menor de 400 líneas.
