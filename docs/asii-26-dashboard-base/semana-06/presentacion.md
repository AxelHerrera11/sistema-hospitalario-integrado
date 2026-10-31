# Tablero de indicadores (dashboard base) (ASII-26) - Primera evaluacion parcial

Diapositivas en Markdown (separadas por `---`); se presentan con Marp o VS Code. Duracion: 8 minutos.

---

## 1. Modulo y alcance

- **Estudiante:** Lis Ivette Rosales Colindrez (`Lis671`) - Issue #209
- **Proceso:** consulta del tablero de indicadores por periodo
- **Problema:** Cada modulo del HIS tiene su pantalla de inicio distinta; no hay un tablero comun por rol ni estados de carga/vacio/error consistentes.

---

## 2. Actores y flujo principal

- Todos los usuarios autenticados (ven tarjetas segun permisos), Direccion medica (indicadores por periodo).
- Evidencia: casos de uso, actividad y secuencia de la [Semana 1](../semana-01/README.md).

---

## 3. Requisitos que guian el diseno

- Consultar el tablero de un periodo con valor, meta y cumplimiento.
- Mostrar menu y tarjetas solo con permiso (falla cerrada).
- Estados uniformes de carga, vacio y error.
- RNF: Dominio sin PDO/HTTP, sentencias preparadas, tenant por cabecera.

---

## 4. Arquitectura en capas

Presentation (MVC) -> Application -> Domain <- Infrastructure. Ver [diagrama de capas](../semana-03/01-capas.svg).

---

## 5. Patrones y SOLID

- MVC en la entrada, Repository en datos, principio **Single Responsibility (SRP)**.
- Matriz decision -> evidencia en el [README](./README.md#matriz-decision---evidencia).

---

## 6. Contrato e integracion

- API REST bajo `/api/v1/tablero`, JWT + `X-Tenant-ID`, sobre comun de errores (ASII-24).
- Integracion con otros modulos solo por contrato, nunca por tablas.

---

## 7. Cambio practico y cierre

- Cambio: Algunos indicadores **mejoran cuando bajan** (tiempo de espera, reingresos).
- Se resuelve tocando una sola capa; el contrato externo no se rompe.
