# Narrativa de alcance y trazabilidad — ASII-03

## Proceso modelado

La Semana 1 modela el proceso de **registro, búsqueda y actualización segura de un paciente** dentro del Sistema Hospitalario Integrado.

El actor principal es el **usuario autorizado**, quien puede registrar, buscar, consultar y actualizar información de pacientes.

## Registro de paciente

El usuario ingresa la información del paciente y el sistema valida los datos antes de almacenarlos.

Si la información es válida, el paciente es registrado y se devuelve una confirmación.

Si existen errores de validación, el registro se rechaza y el sistema informa los datos que deben corregirse.

## Búsqueda y consulta

El usuario proporciona un criterio de búsqueda.

El sistema consulta los pacientes existentes y devuelve las coincidencias encontradas.

Cuando existe un resultado, el usuario puede seleccionar un paciente y consultar su detalle.

Si no existen coincidencias, el sistema informa que no se encontraron pacientes.

## Actualización

Para actualizar información, primero se verifica que el paciente exista.

Si existe, el usuario modifica los datos y el sistema realiza nuevamente las validaciones.

Cuando los datos son válidos, se actualiza el registro.

Si el paciente no existe o los nuevos datos contienen errores, no se realizan cambios.

## Reglas consideradas

- No registrar información inválida.
- No actualizar pacientes inexistentes.
- Validar los datos antes de guardar cambios.
- Una búsqueda sin coincidencias debe producir una respuesta clara.
- Una actualización inválida no debe reemplazar información existente.

## Trazabilidad

| Función | Caso de uso | Actividad | Secuencia |
|---|---|---|---|
| Registro | Registrar paciente | Ingresar → validar → registrar | Usuario → Interfaz → Gestión → BD |
| Búsqueda | Buscar paciente | Buscar → evaluar resultados | Usuario → Interfaz → Gestión → BD |
| Detalle | Consultar detalle | Seleccionar paciente → mostrar | Interfaz → Gestión → BD |
| Actualización | Actualizar paciente | Verificar → validar → actualizar | Usuario → Gestión → BD |
| Validación | Validar datos | Decisión datos válidos | Gestión valida información |
| Existencia | Verificar paciente | Decisión paciente existe | Gestión consulta BD |

## Excepciones

Se contemplan los siguientes escenarios:

- Datos inválidos durante el registro.
- Búsqueda sin coincidencias.
- Paciente inexistente durante una actualización.
- Datos inválidos durante una actualización.

## Resultado

Los tres diagramas representan el mismo proceso desde diferentes perspectivas:

- **Casos de uso:** funciones disponibles para el actor.
- **Actividad:** flujo, decisiones y excepciones.
- **Secuencia:** participantes, mensajes y respuestas.

Con esto se mantiene trazabilidad entre los artefactos solicitados para la Semana 1.
