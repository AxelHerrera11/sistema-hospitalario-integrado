# Guía para una segunda evaluación — `PROPUESTO`

## Recorrido sugerido

1. Explicar el límite del módulo y qué partes están documentadas, integradas o
   pendientes, usando evidencia del repositorio revisado.
2. Presentar la decisión SD-PRE-01 y demostrar cómo se representa cada
   resultado sin crear un flujo de excepción.
3. Explicar por qué cualquier rechazo 422 termina sin persistencia.
4. Describir el sentido de las dependencias y dónde se manejan fallas
   externas.
5. Revisar qué debe existir para validar el contrato, la interfaz y las
   pruebas; separar los pendientes de los resultados reales.

## Preguntas para discusión

- ¿Qué estado tiene cada artefacto y cuál es su fuente?
- ¿Cómo se evita que una falla al leer alergias se convierta en un resultado
  de “sin alergias”?
- ¿Qué evidencia demostraría que un rechazo no escribió datos?
- ¿Qué debe confirmarse antes de integrar un permiso o campo?

Esta guía no presupone respuestas ni evidencia inexistente.
