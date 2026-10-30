# Gestion de citas medicas y agenda (ASII-05) - Primera evaluacion parcial

Diapositivas en Markdown (separadas por `---`); se presentan con Marp o VS Code. Duracion: 8 minutos.

---

## 1. Modulo y alcance

- **Estudiante:** Lis Ivette Rosales Colindrez (`Lis671`) - Issue #207
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Problema:** Recepcion agenda citas en papel y hojas de calculo: hay dobles reservas del mismo doctor o paciente y no queda trazabilidad de cancelaciones.

---

## 2. Actores y flujo principal

- Recepcion (agenda), Medico (consulta su agenda), Coordinacion (confirma/cancela).
- Evidencia: casos de uso, actividad y secuencia de la [Semana 1](../semana-01/README.md).

---

## 3. Requisitos que guian el diseno

- Agendar sin solape de paciente ni doctor (409 `appointments_overlap`).
- Rechazar rango invertido; confirmar y cancelar segun estado.
- Exponer la agenda por API REST bajo `/api/v1/citas`.
- RNF: Dominio sin PDO/HTTP, sentencias preparadas, tenant por cabecera.

---

## 4. Arquitectura en capas

Presentation (MVC) -> Application -> Domain <- Infrastructure. Ver [diagrama de capas](../semana-03/01-capas.svg).

---

## 5. Patrones y SOLID

- MVC en la entrada, Repository en datos, principio **Dependency Inversion (DIP)**.
- Matriz decision -> evidencia en el [README](./README.md#matriz-decision---evidencia).

---

## 6. Contrato e integracion

- API REST bajo `/api/v1/citas`, JWT + `X-Tenant-ID`, sobre comun de errores (ASII-24).
- Integracion con otros modulos solo por contrato, nunca por tablas.

---

## 7. Cambio practico y cierre

- Cambio: La clinica pide **10 minutos de holgura** entre citas del mismo doctor.
- Se resuelve tocando una sola capa; el contrato externo no se rompe.
