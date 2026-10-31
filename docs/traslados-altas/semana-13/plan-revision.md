# Plan de revisión técnica formal — ASII-08

**Objeto:** `micro-his/traslados-altas/` en las cuatro capas, la API del prototipo (`public/index.php` y `docs/traslados-altas/semana-05/openapi.yaml`) y el prototipo de interfaz (`public/ui/`).
**Foco:** reglas de traslado y alta, API, tenant, permisos e integridad de HOSPITAL.

> Este documento **prepara** la revisión. No registra ninguna reunión ni hallazgo de revisores porque todavía no ocurrió. La asignación no exige una reunión: si se hace, se registra aquí.

## Roles propuestos (nadie los aceptó todavía)

| Rol | Persona propuesta | Responsabilidad |
|---|---|---|
| Autora | María Yamilet Lindo Pablo (`Yamilet1235`) | Preparar el paquete, responder los hallazgos y corregir |
| Moderador | Por confirmar | Decidir la salida |
| Revisor de permisos | Por confirmar | Autorización antes de la transacción y correspondencia con los permisos |
| Revisor de integridad | Por confirmar | `beds.status`, R-TA-01 y la exclusión por cama |
| Revisor de consumidores | Por confirmar | Efecto de traslados y altas en la lectura de ocupación |

## Criterios de entrada

| # | Criterio | Estado verificable hoy (21/10/2026, develop `03675bf`) |
|---|---|---|
| E1 | Suites del micro-HIS en verde | Sí: `run.php` 95/95, `postgres.php` 20/20 y `ui-mensajes` 7/7 |
| E2 | Contrato de la API válido | Sí: `openapi.yaml` parseado y sin `$ref` rotos (Semana 5) |
| E3 | Cada PR de 400 líneas o menos, con su evidencia | Sí: #790–#884 |
| E4 | Roles confirmados | **No**: propuestos |
| E5 | Paquete entregado a los revisores | **No** |

## Procedimiento

1. **Preparación (autora):** este plan, el [checklist](./checklist.md) con su autoverificación y los enlaces a los PR.
2. **Revisión individual:** cada revisor recorre el checklist y anota sus hallazgos en el formato de abajo.
3. **Consolidación (moderador):** se ordenan los hallazgos por severidad; los duplicados se unen.
4. **Corrección (autora):** cada hallazgo mayor o crítico se cierra con un commit y una prueba que lo demuestre.

## Criterios de salida

- Ningún hallazgo crítico abierto.
- Cada hallazgo mayor está cerrado o tiene un responsable y una fecha.
- El moderador registra la decisión: aceptado, aceptado con cambios o rechazado.

## Formato de hallazgos

| ID | Ítem del checklist | Severidad (crítico, mayor, menor) | Archivo:línea | Descripción | Evidencia | Estado |
|---|---|---|---|---|---|---|

Hoy no hay ningún hallazgo de revisores registrado.
