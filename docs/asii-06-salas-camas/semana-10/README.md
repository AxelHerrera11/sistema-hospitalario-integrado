# ASII-06 — Semana 10: diseño responsive y móvil

## Alcance académico identificado

La plantilla `.github/ISSUE_TEMPLATE/module_task.md` define Semana 10 como
**“diseño responsive/móvil”**. El issue maestro #11 mantiene el objetivo y
Definition of Done generales del módulo, pero no especifica criterios ni
entregables semanales adicionales; no tiene comentarios del docente/reviewer.
Se toma el alcance de la plantilla como el requisito oficial disponible.

Esta entrega diseña la consulta de wards y camas para anchos móviles, siguiendo
el flujo UX de Semana 8 y los hallazgos de Semana 9. Usa como referencia de
formato los entregables móviles de Semana 10 de Pacientes. Los wireframes son
especificaciones, no pantallas operativas.

## Entregables

- [Estrategia responsive](./estrategia-responsive.md): anchos, reflow,
  contenido, interacción y accesibilidad móvil.
- [Pantallas móviles](./pantallas-moviles.md): wireframes anotados del flujo
  wards-camas y estados.
- [Escenarios móviles](./escenarios-moviles.md): recorridos y resultados
  esperados en móvil.
- [Evidencia de integración](./evidencia-integracion.md): referencias
  inspeccionadas, decisiones, validaciones y plan de integración de Semana 11.

## Alcance funcional y límites

El diseño se limita a consultar `wards` y `beds` con las operaciones
existentes:

- `GET /api/v1/wards`
- `GET /api/v1/wards/{ward}/beds`

La futura pantalla conservará el permiso existente `camas.ver` y la
autorización del servidor. No se agregan `rooms`, acciones CRUD, admisiones,
ocupación clínica, estancias, traslados, altas ni asignación de pacientes.
No se modifican API, frontend, permisos, persistencia ni migraciones en esta
semana. La implementación Vue y la verificación en navegador se reservan para
Semana 11, conforme a la secuencia de la plantilla.

## Riesgos conocidos

Los estados se muestran como valores de catálogo, no como inferencias clínicas.
Las etiquetas en español requieren acuerdo funcional. `TenantMiddleware`
valida el hospital, pero la conexión `hospital` sigue fija y no existe
selección dinámica de base de datos por tenant; no hay aislamiento
multi-tenant completo.
