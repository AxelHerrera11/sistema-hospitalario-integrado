# Checklist de revisión

Cada punto se marca **Cumple**, **No cumple** (abre un hallazgo) o **No aplica**.

## Dominio y reglas

- [ ] Las reglas de negocio están en el dominio y no en controladores ni en SQL.
- [ ] Cada regla incumplida produce un código estable (`DomainRuleViolation`) y el campo afectado.
- [ ] Los casos de uso citan los UC correctos de la Semana 1.

## Persistencia y datos

- [ ] Solo sentencias preparadas; ningún SQL armado con texto del usuario.
- [ ] Las operaciones que deben ser atómicas están en una transacción con rollback.
- [ ] La unicidad y la concurrencia las decide la base (índices, `UPDATE` condicionado).
- [ ] El módulo solo escribe sus propias tablas.

## Seguridad y datos clínicos

- [ ] Toda ruta exige tenant y usuario; las de escritura, un permiso `modulo.accion`.
- [ ] Los logs y notificaciones no contienen datos clínicos.
- [ ] La falla segura está implementada y probada.

## Contrato API

- [ ] `openapi.yaml` valida sin advertencias y cada ruta declarada existe en el servidor.
- [ ] Los errores usan `problem+json` con `code` y `request_id`.
- [ ] El cliente solo reintenta operaciones idempotentes.

## UI y accesibilidad

- [ ] Estados vacío, carga, éxito y error recuperable presentes.
- [ ] Objetivos de 44 px, foco visible, etiquetas y mensajes con texto (WCAG 2.1 AA).

## Pruebas y documentación

- [ ] Las pruebas corren con código 0 y cubren camino feliz, reglas y fallas.
- [ ] README, diagramas y código dicen lo mismo (nombres, firmas y responsabilidades).
- [ ] Lo no implementado está marcado como «propuesto».
