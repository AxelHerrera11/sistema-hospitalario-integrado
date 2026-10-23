# Ejercicio sintético — `PROPUESTO`

## Escenario

Una persona profesional prepara una prescripción para un paciente ficticio.
Durante la verificación se recibe una clasificación de alergia y el sistema
debe presentar el resultado correspondiente. Los nombres y valores de este
ejercicio son marcadores y no representan un paciente real ni una recomendación
médica.

## Tareas del participante

- Clasificar el resultado como advertencia, rechazo con excepción permitida o
  rechazo sin excepción según SD-PRE-01.
- Explicar qué debe comunicar la interfaz y qué no debe persistir.
- Identificar qué evidencia técnica sería necesaria para probar el resultado.

## Criterio de corrección

Una respuesta correcta mantiene separados los tres resultados clínicos,
rechaza sin guardar en todos los casos 422, no modifica `isGrave()` y no
propone endpoint de excepción. No se asigna una nota en este documento.
