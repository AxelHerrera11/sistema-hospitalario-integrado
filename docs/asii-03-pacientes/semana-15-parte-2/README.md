# ASII-03 Pacientes — Parte 2: integración

## Alcance y resultado

Esta rama parte de `d6af6356` (Semana 15, evidencia) y conecta la pantalla Vue
existente a las rutas Laravel GET, POST y PATCH. El flujo emplea el cliente
Axios compartido para token y `X-Tenant-ID`, `can()` para visibilidad básica
y RBAC de servidor como autoridad. El prototipo con pacientes inventados se
retiró del modo normal.

## Estado de integración

- Vue conectado en código a búsqueda, alta y edición, con carga, resultado y
  mensajes para 401/403/404/409/422 y error general.
- Build confirma compilación, no ejecución con una sesión o API real.
- PostgreSQL hospital no disponible; no se ejecutó persistencia real ni se
  modificó la base.
- Tenant y JWT reales pendientes: los tests HTTP previos sustituyen ambos.
- La política `RequirePermission` y matriz RBAC sí se ejecutaron con store en
  memoria; membresías/JWT y tenant productivos no se ejercitaron.
- No se verificó un recorrido extremo a extremo con PostgreSQL.

## Limitaciones / siguiente paso

El hospital está configurado como conexión fija; no se confirmó selección
dinámica por tenant. El siguiente paso es levantar PostgreSQL de pruebas,
aplicar las migraciones de entorno y ejecutar el recorrido HTTP con login,
tenant activo y roles reales. Ver [evidencia](./evidencia-integracion.md).
