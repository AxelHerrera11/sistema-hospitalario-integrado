# Requisitos de Prescripciones

Estado: diseño documentado; confirmar contra la implementación al integrarla.

## Funcionales

- **RF-01 — Consultar catálogo:** resolver el medicamento solicitado y
  distinguir un identificador inexistente o inactivo de una falla de consulta.
- **RF-02 — Evaluar alergias:** consultar las alergias activas del paciente
  antes de aceptar la prescripción.
- **RF-03 — Advertir conflicto leve:** ante conflicto `mild`, permitir el
  resultado con una advertencia explícita.
- **RF-04 — Rechazar conflicto moderado o desconocido:** responder 422 e
  indicar `exception_allowed: true`; no persistir el intento.
- **RF-05 — Rechazar conflicto grave:** para `severe` o `life_threatening`,
  responder 422 sin permitir excepción y sin persistir.
- **RF-06 — Auditar eventos:** enviar los eventos de dominio/aplicación al
  límite de auditoría y representar explícitamente su indisponibilidad.
- **RF-07 — Firmar:** conservar la distinción entre prescripción creada y
  prescripción firmada; los detalles del flujo de firma se validan en su tarea
  de integración.

## No funcionales

- **RNF-01 — Dependencias invertidas:** la política de aplicación depende de
  contratos, no de PDO, HTTP, Laravel ni almacenamiento concreto.
- **RNF-02 — Fallos explícitos:** catálogo, alergias y auditoría diferencian
  indisponibilidad de un resultado exitoso vacío.
- **RNF-03 — Integridad clínica:** el rechazo no cambia datos persistidos.
- **RNF-04 — Trazabilidad:** cada rechazo registra su categoría sin exponer
  información clínica innecesaria.
- **RNF-05 — Compatibilidad de dominio:** no alterar `isGrave()` como parte de
  esta decisión.

## Fuera de alcance

La excepción de alergia sigue pendiente: no hay autorización de producto para
crear su endpoint ni para persistir una excepción.
