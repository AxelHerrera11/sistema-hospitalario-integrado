# Narrativa de alcance y trazabilidad — ASII-28

## Proceso modelado

La Semana 1 modela el proceso de **diseño de casos manuales, campaña de regresión, evidencia, defectos y trazabilidad**.

El actor principal es el **Analista QA**, encargado de preparar y ejecutar pruebas manuales.

También participa el **Responsable de corrección**, quien consulta los defectos detectados durante las pruebas.

## Diseño del caso manual

El Analista QA define un caso de prueba especificando los pasos necesarios y el resultado esperado.

Antes de guardar el caso, el sistema verifica que la información esté completa.

Si falta información, el caso no se considera válido y debe ser corregido.

## Campaña de regresión

Una campaña agrupa los casos que deben ejecutarse para validar una versión o conjunto de funcionalidades.

El Analista QA crea la campaña y agrega los casos correspondientes.

## Ejecución

Durante la ejecución se realiza cada caso manual y se registra la evidencia correspondiente.

El resultado obtenido se compara con el resultado esperado.

Si coinciden, el caso se registra como aprobado.

Si no coinciden, se registra como fallido.

## Registro de defectos

Cuando un caso falla, se crea un defecto relacionado con:

- Caso de prueba.
- Campaña.
- Ejecución.
- Evidencia.

El defecto queda disponible para que el responsable correspondiente pueda revisarlo.

## Reglas consideradas

- Un caso manual debe contener información suficiente antes de ejecutarse.
- Una campaña debe contener casos de prueba.
- Cada ejecución debe registrar un resultado.
- La evidencia debe asociarse con la ejecución correspondiente.
- Un resultado fallido puede generar un defecto.
- Los defectos deben conservar trazabilidad con el caso y la campaña.

## Excepciones

Se contemplan los siguientes escenarios:

- Caso de prueba incompleto.
- Campaña sin casos asociados.
- Falta de evidencia durante una ejecución.
- Resultado diferente al esperado.
- Registro de un defecto por fallo funcional.

## Trazabilidad

| Elemento | Caso de uso | Actividad | Secuencia |
|---|---|---|---|
| Caso manual | Diseñar caso | Definir pasos → validar | QA → Interfaz → Gestión |
| Campaña | Crear campaña | Crear → agregar casos | QA → Gestión → BD |
| Ejecución | Ejecutar caso | Ejecutar → comparar resultado | QA → Gestión de pruebas |
| Evidencia | Registrar evidencia | Adjuntar evidencia | Gestión → BD |
| Resultado | Registrar resultado | Aprobar o fallar | Gestión compara resultado |
| Defecto | Registrar defecto | Crear ante fallo | Pruebas → Defectos → BD |
| Trazabilidad | Relacionar elementos | Vincular campaña, caso y defecto | Gestión → BD |

## Resultado

Los tres diagramas representan el mismo proceso desde diferentes perspectivas:

- **Casos de uso:** operaciones disponibles para los actores.
- **Actividad:** decisiones, excepciones y resultados.
- **Secuencia:** participantes, mensajes, validaciones y respuestas.

Con esto se mantiene la trazabilidad requerida para la Semana 1.
