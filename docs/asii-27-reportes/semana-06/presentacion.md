# Reportes exportables (ASII-27) - Primera evaluacion parcial

Diapositivas en Markdown (separadas por `---`); se presentan con Marp o VS Code. Duracion: 8 minutos.

---

## 1. Modulo y alcance

- **Estudiante:** Lis Ivette Rosales Colindrez (`Lis671`) - Issue #210
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Problema:** Los reportes operativos se arman a mano copiando datos de varios modulos; no son reproducibles ni se sabe con que filtros se generaron.

---

## 2. Actores y flujo principal

- Administracion (genera y exporta), Jefaturas (consultan), Auditoria (verifica reproducibilidad).
- Evidencia: casos de uso, actividad y secuencia de la [Semana 1](../semana-01/README.md).

---

## 3. Requisitos que guian el diseno

- Generar reportes inmutables por periodo (422 `reporte_invalido`).
- Filtrar por rango (max. 366 dias) y exportar CSV reproducible.
- Agregar formatos sin modificar el nucleo (OCP).
- RNF: Dominio sin PDO/HTTP, sentencias preparadas, tenant por cabecera.

---

## 4. Arquitectura en capas

Presentation (MVC) -> Application -> Domain <- Infrastructure. Ver [diagrama de capas](../semana-03/01-capas.svg).

---

## 5. Patrones y SOLID

- MVC en la entrada, Repository en datos, principio **Open/Closed (OCP)**.
- Matriz decision -> evidencia en el [README](./README.md#matriz-decision---evidencia).

---

## 6. Contrato e integracion

- API REST bajo `/api/v1/reportes`, JWT + `X-Tenant-ID`, sobre comun de errores (ASII-24).
- Integracion con otros modulos solo por contrato, nunca por tablas.

---

## 7. Cambio practico y cierre

- Cambio: Administracion pide exportar en **XLSX**.
- Se resuelve tocando una sola capa; el contrato externo no se rompe.
