# Declaración de uso de inteligencia artificial

## Herramienta y propósito

Se utilizó GitHub Copilot en VS Code como apoyo para organizar y redactar la
propuesta documental de responsabilidades MVC, Repository e integración de
datos compartidos. No se utilizó para afirmar una implementación no verificada.

## Instrucciones relevantes

- Documentar el diseño MVC y el límite Repository de Prescripciones.
- Mantener el controlador sin SQL ni reglas de negocio y distinguir ausencia
  de registro de fallas de persistencia.
- Mantener SD-PRE-01, no guardar ningún rechazo 422, no cambiar `isGrave()` y
  dejar `excepcion_alergia` pendiente, sin endpoint.
- Identificar como propuesta lo que no tenga evidencia de estar integrado.

## Uso y cambios humanos

Se aceptó el apoyo para estructurar el análisis y redactar un diagrama de
integración. Se modificó el contenido para no inventar firmas, esquema ni
estado operativo; `PrescriptionRepository` y `PrescriptionService` se
identifican como `PROPUESTO`. La propuesta relacionada está **en revisión en
#900**; este documento no declara que su código esté integrado en `develop`.

## Validación humana

La persona usuaria confirmó el alcance documental y las reglas clínicas que
deben preservarse. La revisión final de esta declaración y del contenido del
PR queda pendiente de su aprobación; no se afirma que se haya ejecutado una
prueba de usabilidad o integración.
