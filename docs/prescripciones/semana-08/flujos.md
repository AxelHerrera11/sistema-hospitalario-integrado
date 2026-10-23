# Flujos de usuario propuestos

## Médico — preparar y revisar una prescripción

1. Abre el módulo y localiza al paciente con el contexto hospitalario visible.
2. Selecciona el medicamento disponible y completa los datos exigidos por el
   contrato vigente; el wireframe no fija unidades ni formato de dosis.
3. Solicita la validación de alergias.
4. Si no hay conflicto, revisa el resumen y solicita guardar.
5. Si el resultado es `mild`, lee la advertencia antes de continuar.
6. Si es `moderate` o `unknown`, recibe el rechazo 422 y el aviso de que no se
   guardó; si es `severe` o `life_threatening`, recibe el rechazo 422 sin
   opción de excepción. En ambos casos el flujo termina sin persistencia.
7. Para firmar, revisa el detalle y confirma la acción; el resultado final se
   muestra según la respuesta del servicio.

## Enfermería — consultar el estado

Flujo propuesto de consulta: localizar al paciente, abrir el detalle y leer
el estado y las advertencias. Permisos, datos visibles y acciones de este rol
están `PENDIENTE` de validación; este documento no concede permiso para crear,
firmar o modificar.

## Farmacia — revisar el detalle

Flujo propuesto de lectura: localizar la prescripción y consultar su detalle
para continuar el proceso farmacéutico definido por el módulo. Las acciones
permitidas, el estado de dispensación y su integración están `PENDIENTE`; no
se afirma una operación de dispensación.

## Excepciones de interacción

- Servicio de alergias no disponible: mostrar error recuperable y no simular
  una lista vacía ni un éxito.
- Validación de campos: conservar entradas no sensibles y asociar cada error
  al campo correspondiente.
- 422 clínico: mostrar motivo legible, estado de no guardado y siguiente paso
  permitido; nunca ofrecer un endpoint de excepción.
