# ASII-03 — Pacientes: registro, edición, búsqueda y detalle

## Semana 1 — UML y narrativa de alcance

**Estudiante:** Madelin Jazmín Cerón Molina  
**GitHub:** `@MadelinCeron`  
**Módulo:** ASII-03 — Pacientes  
**Proceso:** registro, búsqueda y actualización segura de un paciente.

## Objetivo

Modelar el proceso básico de gestión de pacientes del Sistema Hospitalario Integrado, identificando actores, validaciones, decisiones, excepciones y respuestas.

## Alcance

Esta semana contempla:

- Registro de pacientes.
- Validación de datos.
- Búsqueda de pacientes.
- Consulta de detalle.
- Verificación de existencia.
- Actualización segura.
- Manejo de errores y búsquedas sin resultados.

No se implementan todavía API, controladores, servicios ni interfaz de usuario.

## Diagramas

### Casos de uso

![Casos de uso](./01-casos-de-uso.svg)

Representa las operaciones principales del usuario autorizado:

- Registrar paciente.
- Buscar paciente.
- Consultar detalle.
- Actualizar paciente.
- Validar datos.
- Verificar existencia.

### Actividad

![Actividad](./02-actividad.svg)

Representa las decisiones y excepciones de registro, búsqueda y actualización.

### Secuencia

![Secuencia](./03-secuencia.svg)

Representa la comunicación entre:

- Usuario autorizado.
- Interfaz.
- Gestión de pacientes.
- Base de datos HOSPITAL.

## Evidencia

La narrativa de alcance y la trazabilidad entre los diagramas se encuentran en:

`narrativa-alcance-trazabilidad.md`

## Resultado

La Semana 1 deja definido el comportamiento esperado del proceso antes de iniciar las etapas posteriores de diseño e implementación.
