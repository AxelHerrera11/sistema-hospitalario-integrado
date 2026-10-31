# Centro de documentacion y manuales por rol (ASII-24) - Primera evaluacion parcial

Diapositivas en Markdown (separadas por `---`); se presentan con Marp o VS Code. Duracion: 8 minutos.

---

## 1. Modulo y alcance

- **Estudiante:** Lis Ivette Rosales Colindrez (`Lis671`) - Issue #208
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Problema:** Los manuales y contratos API estan dispersos (Drive, chats, PDFs) y cada rol ve documentos que no le corresponden o versiones viejas.

---

## 2. Actores y flujo principal

- Administrador de documentacion (publica), personal clinico y administrativo (consulta segun su rol), desarrolladores (consumen OpenAPI).
- Evidencia: casos de uso, actividad y secuencia de la [Semana 1](../semana-01/README.md).

---

## 3. Requisitos que guian el diseno

- Publicar documentos con al menos un rol destinatario (422 `documento_sin_roles`).
- Listar solo el catalogo visible para el rol de la sesion.
- Mantener `openapi.yaml` y el formato comun de errores como contrato unico.
- RNF: Dominio sin PDO/HTTP, sentencias preparadas, tenant por cabecera.

---

## 4. Arquitectura en capas

Presentation (MVC) -> Application -> Domain <- Infrastructure. Ver [diagrama de capas](../semana-03/01-capas.svg).

---

## 5. Patrones y SOLID

- MVC en la entrada, Repository en datos, principio **Interface Segregation (ISP)**.
- Matriz decision -> evidencia en el [README](./README.md#matriz-decision---evidencia).

---

## 6. Contrato e integracion

- API REST bajo `/api/v1/documentos`, JWT + `X-Tenant-ID`, sobre comun de errores (ASII-24).
- Integracion con otros modulos solo por contrato, nunca por tablas.

---

## 7. Cambio practico y cierre

- Cambio: Los manuales deben tener **version** y solo debe mostrarse la **vigente**.
- Se resuelve tocando una sola capa; el contrato externo no se rompe.
