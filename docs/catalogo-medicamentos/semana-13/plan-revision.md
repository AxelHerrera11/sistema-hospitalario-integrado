# Plan de revisión técnica formal - ASII-14

**Objeto:** `micro-his/catalogo-medicamentos/` (Domain, Application, Persistence, Presentation, `bin/`, `public/`) y los contratos de `docs/catalogo-medicamentos/semana-05/`. **Foco:** dominio, API, permisos y tenant, integridad y dependencias estrictamente necesarias. **Fuera de foco:** estilo, UI simulada y documentación narrativa.

## Roles (propuestos; ninguno ha participado todavía)

| Rol | Persona propuesta | Responsabilidad |
|---|---|---|
| Autora | María Yamilet Lindo Pablo (`Yamilet1235`) | Preparar el paquete, responder hallazgos y corregir |
| Moderador | Por confirmar | Conducir la reunión y decidir la salida |
| Revisor 1: dominio e integridad | Responsable de Prescripciones, por confirmar | Reglas de vigencia y unicidad, y uso por consumidores |
| Revisor 2: permisos y tenant | Responsable de RBAC (ASII-02), por confirmar | Matriz de permisos y aislamiento por hospital |
| Registrador | Autora o moderador | Llenar la tabla de hallazgos |

## Criterios de entrada

| # | Criterio | Estado verificable hoy |
|---|---|---|
| E1 | Suites del micro-HIS en verde con código 0 | Sí: run 47, pdo 18, repository 19, http 17 (`EVIDENCIA` de esta semana) |
| E2 | Contrato OpenAPI válido | Sí: `redocly lint` válido (Semana 5) |
| E3 | Paquete con alcance y diff de cada PR (≤ 400 líneas) | Sí: PR #572 y apilados #584–#597 |
| E4 | Roles confirmados y fecha acordada | **No**: roles propuestos |
| E5 | Cada revisor recibió el paquete 48 h antes | **No** |

## Procedimiento

1. **Preparación (autora):** paquete, checklist y autoverificación.
2. **Revisión individual (revisores):** 60 minutos con el checklist; cada hallazgo en el formato de abajo.
3. **Reunión (moderador):** como máximo 45 minutos; se registran los hallazgos sin discutir soluciones.
4. **Corrección (autora):** cada hallazgo mayor o crítico con commit y prueba que lo demuestre.
5. **Seguimiento (moderador):** verifica las correcciones y decide la salida.

## Criterios de salida

| # | Criterio |
|---|---|
| S1 | 0 hallazgos críticos abiertos |
| S2 | Cada hallazgo mayor corregido con su prueba, o aceptado por el moderador con plan y fecha |
| S3 | Suites en verde tras las correcciones |
| S4 | Decisión registrada: aprobado, aprobado con cambios menores o nueva revisión |

## Formato de hallazgos

| ID | Ítem del checklist | Archivo:línea | Descripción | Severidad (crítica, mayor, menor) | Decisión | Commit o prueba |
|---|---|---|---|---|---|---|

Hoy no hay ningún hallazgo de revisores registrado: la revisión no se ha realizado.
