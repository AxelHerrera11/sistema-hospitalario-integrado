---
marp: true
title: Expediente médico electrónico base — Primera evaluación
---

# Expediente médico electrónico base

**Apertura y consulta autorizada del expediente longitudinal**

Micro-HIS PHP 8.2 vanilla · integrado con RBAC, Gobernanza, Pacientes y Laravel

---

## 1. Actores y alcance (Semana 1)

- Médico tratante: consulta y abre · Enfermería: permiso sí, vínculo no (S-01) · Recepción: abre (S-02)
- Administración: sin acceso clínico (403)
- Fuera de alcance: escribir notas, alergias o episodios; acceso entre hospitales
- UML: casos de uso, actividad (C1…C7, A1…A7) y secuencia con las clases reales

---

## 2. Requisitos y SOLID (Semana 2)

- RF-EXP-01…06 · RNF-EXP-01…08 · criterios CA-01…CA-10 enlazados a pruebas
- DIP: los casos de uso dependen de `AccessAuthorizer`, `MedicalRecordRepository`, `CareRelationship`,
  `PatientDirectory`, `ClinicalAccessAudit`, `HospitalStoresProvider`
- Evidencia: R-01…R-06 y el mismo flujo con PDO e InMemory

---

## 3. Capas, MVC y Repository (Semanas 3–4)

- Presentation → Application → Domain; Persistence e Integration implementan puertos
- Controlador sin SQL ni reglas (R-03, R-04)
- Repository en Application; PDO con sentencias preparadas e InMemory con el mismo contrato
- Base federada: el expediente escribe solo `medical_records`; lee episodios y asignaciones

---

## 4. Contrato API e integración (Semana 5)

- `GET|POST /patients/{code}/medical-record` · `openapi.yaml`
- Laravel: `tenant → jwt.auth → rbac:expediente.ver` + `Authorize` y `RecordAuditEvent` del contenedor
- Microservicio evaluado y descartado con medición: 0.12 ms en proceso vs. ~78 ms por salto HTTP local

---

## 5. Seguridad, integridad y datos sensibles

- Acceso en dos pasos (permiso + vínculo); paciente inexistente no se revela
- Sin evento de auditoría no hay datos (Security); `no-store`; errores con solo `code`
- Un expediente por paciente; transacción INSERT + auditoría; nunca se borra
- Una base por hospital (`HospitalRegistry`)

---

## 6. Cambio práctico defendido

- Pedido: funcionar con el JWT real (usuario CENTRAL) aunque el vínculo use `local_user_refs`
- Cambio: `RequestContext::localUserRef()` + `ResolutorActorLocal` de Traslados en el adaptador Laravel
- Evidencia: C8 y `tests/Feature/ExpedienteMedico` · sin cambios en el contrato ni en la base

---

## 7. Pendientes reales

- RBAC (María): permiso de apertura y criterio para enfermería
- Registro del provider en `bootstrap/providers.php`: hecho (autorizado el 2026-10-23)
- Formato común de errores (Lis) · validación en PostgreSQL (no disponible en el entorno)
