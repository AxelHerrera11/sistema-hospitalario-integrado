# Narrativa de alcance y trazabilidad — ASII-06

## Proceso modelado

La Semana 1 modela el proceso de **creación de sala y cama con control de estado operativo**.

El actor principal es el **usuario autorizado**, encargado de registrar la estructura hospitalaria necesaria para administrar posteriormente la ocupación de camas.

## Creación de sala

El usuario proporciona la información de la sala o ward.

El sistema valida los datos antes de almacenarlos.

Si la información es válida, la sala se registra.

Si existen errores, el sistema rechaza la operación e informa los datos que deben corregirse.

## Creación de cama

Para registrar una cama, el usuario proporciona sus datos y selecciona la sala a la que pertenecerá.

El sistema verifica que la sala exista.

Si la sala existe, se valida el estado operativo de la cama.

Cuando la información es válida, la cama se registra y queda asociada a la sala correspondiente.

## Estados operativos

Los estados contemplados actualmente para las camas son:

- `available`
- `occupied`
- `maintenance`
- `inactive`

Los estados contemplados para las salas o wards son:

- `active`
- `inactive`

## Reglas consideradas

- Una cama debe pertenecer a una sala existente.
- No se debe registrar una cama con un estado no permitido.
- Los datos de una sala deben validarse antes de registrarse.
- Una asociación inválida entre sala y cama debe rechazarse.
- Una operación incorrecta debe devolver una respuesta comprensible.

## Excepciones

Se contemplan los siguientes escenarios:

- Datos inválidos durante la creación de una sala.
- Sala inexistente al intentar registrar una cama.
- Estado operativo inválido.
- Información incompleta durante el registro.

## Trazabilidad

| Función | Caso de uso | Actividad | Secuencia |
|---|---|---|---|
| Crear sala | Crear sala / ward | Ingresar → validar → registrar | Usuario → Interfaz → Gestión → BD |
| Crear cama | Crear cama | Ingresar → verificar sala → registrar | Usuario → Interfaz → Gestión → BD |
| Verificar sala | Verificar sala existente | Decisión sala existe | Gestión consulta BD |
| Validar estado | Validar estado operativo | Decisión estado válido | Gestión valida estado |
| Asociación | Asociar cama a sala | Registrar relación | Gestión → BD |

## Resultado

Los tres diagramas representan el mismo proceso:

- **Casos de uso:** operaciones disponibles para el actor.
- **Actividad:** decisiones, validaciones y excepciones.
- **Secuencia:** participantes, mensajes y respuestas.

La correspondencia entre los tres artefactos permite mantener la trazabilidad requerida para la Semana 1.
