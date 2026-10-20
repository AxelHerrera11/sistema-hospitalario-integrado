# Notas SOAP y diagnósticos — Contrato API

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-11 — Notas SOAP y diagnósticos
- **Entregable:** contrato API de integración publicado antes del código, según el plan de integración del 17 al 23 de octubre.

## Archivos

- [Contrato API v1](./contrato-api.md): endpoints, campos, permisos `modulo.accion`, errores `401`/`403`/`422` y propiedad de datos.
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Pendientes de integración

| Con quién | Qué |
|---|---|
| María (ASII-02, RBAC) | Alta de los permisos `notas_soap.*` y la matriz de roles propuesta |
| Javier (base de datos) | Tablas `icd10_codes` y `local_access_audits`, que no están en las migraciones |
| Episodios / Admisión | Lectura de `care_assignments` activas por `doctor_profile_id` |
