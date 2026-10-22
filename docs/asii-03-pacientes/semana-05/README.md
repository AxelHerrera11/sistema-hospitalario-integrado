# ASII-03 Pacientes - Semana 05

## Entrega

Define el contrato cliente-servidor REST del Micro-HIS de Pacientes y evalúa su límite de servicio sin dividir físicamente el sistema.

- [Contrato API](./contrato-api.md): rutas, permisos, errores y límites del contrato.
- [Plan de integración](./plan-integracion.md): consumo, ownership, seguridad, resiliencia y decisión arquitectónica.
- Contrato OpenAPI 3.1: [`micro-his/pacientes/openapi.yaml`](../../../../micro-his/pacientes/openapi.yaml).

## Base en el código

Semana 04 ya separa Presentation, Application, Domain y Persistence. `PatientService` implementa registro, consulta por código y actualización; `Patient` valida fecha, sexo y estado; `PdoPatientRepository` lee/escribe `local_patients` y su `UPDATE` solo asigna campos demográficos editables. Las pruebas usan `InMemoryPatientRepository`.

El contrato describe una futura frontera HTTP, no un servidor desplegable: `PatientController` devuelve arreglos de aplicación y no procesa headers ni serializa `application/problem+json`. No se agrega framework, infraestructura, migración ni tabla.

## Decisión

Mantener Pacientes como módulo modular del HIS por ahora. La extracción física requiere primero desacoplar las relaciones clínicas que referencian `local_patients` y demostrar una necesidad operativa independiente.
