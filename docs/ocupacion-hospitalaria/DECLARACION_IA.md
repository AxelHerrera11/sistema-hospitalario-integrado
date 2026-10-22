# Declaración de uso de inteligencia artificial

**Herramienta:** OpenCode con el modelo MiniMax M3.1 Flash.

**Propósito:** redactar el contrato de integración de la API de ocupación
hospitalaria y el índice del módulo a partir de la entrega de la semana 5, el
esquema HOSPITAL existente en `develop` y el plan transversal de integración
recibido el 18 de octubre de 2026.

**Prompt relevante:** «Compartir a más tardar el martes 20 el contrato de
ocupación ya preparado en Semana 5, alineado con los contratos comunes de auth,
RBAC, tenant y errores», dentro del plan transversal que asigna a Javier el
contrato de ocupación y el de órdenes de laboratorio.

**Contenido aceptado o modificado:** la semántica de ocupación (cama ocupada en
el corte `T`), el formato de errores, los límites de resiliencia y la
observabilidad provienen de las semanas 1 a 5 ya aprobadas por el estudiante.
Se ajustaron los nombres físicos de tablas (`wards`, `beds`, `bed_stays`,
`care_episodes`) contra las migraciones HOSPITAL reales de `develop` y se
marcaron como pendientes de confirmación del equipo el permiso `ocupacion.ver`,
el formato de `X-Tenant-ID` y el corte por defecto.

**Validación humana:** pendiente. Javier Alexander Fajardo López debe revisar
el contrato y esta declaración antes de que el PR se integre a `develop`.
