# Wireframes de baja fidelidad — `PROPUESTO`

Los bloques son pantallas conceptuales para revisar estructura y secuencia,
no una definición de API ni de campos finales.

## Crear

```text
+----------------------------------------------------+
| Prescripciones                         Hospital: … |
| Paciente: [identidad confirmada]                    |
+----------------------------------------------------+
| Medicamento                                          |
| [Buscar y seleccionar............................]  |
| Datos requeridos por el contrato aprobado            |
| [Campo]                 [Campo]                      |
| [Campo]                                               |
| [Validar y revisar]                                   |
+----------------------------------------------------+
| Errores junto al campo; no continuar hasta corregir  |
+----------------------------------------------------+
```

## Revisión sin conflicto o advertencia `mild`

```text
+----------------------------------------------------+
| Revisar prescripción                                 |
| Paciente: [identidad]     Medicamento: [selección]  |
| Datos: [resumen aprobado]                            |
| Estado de alergias: sin conflicto / advertencia     |
| [Volver a editar]                     [Guardar]      |
+----------------------------------------------------+
```

## Rechazo por alergia

```text
+----------------------------------------------------+
| No se guardó la prescripción                        |
| Conflicto: [descripción segura y legible]            |
| El nivel impide continuar. Revise la información     |
| del paciente y contacte el flujo clínico vigente.    |
| [Volver al borrador]                                 |
+----------------------------------------------------+
```

No hay botón para registrar una excepción ni una confirmación que convierta
el rechazo en guardado.

## Detalle y firma

```text
+----------------------------------------------------+
| Detalle de prescripción                              |
| Estado: [respuesta del servicio]                     |
| Paciente / medicamento / datos aprobados             |
| [Volver]                              [Firmar]       |
+----------------------------------------------------+
| Confirmación: revisar paciente y contenido           |
| [Cancelar]                          [Confirmar]      |
+----------------------------------------------------+
```

La acción de firmar, su reversibilidad y los campos del resumen deben
confirmarse en el contrato y reglas de dominio antes de implementar.
