# Roles de laboratorio coordinados con RBAC

Los códigos canónicos nuevos son `tecnico_laboratorio` y `bioquimico`.
Se aceptan literalmente al asignar membresías, resolver roles y emitir la
versión de roles de una sesión. No se traducen `TecnicoLab` ni `Bioquimico`.

- Técnico: muestras y consulta de órdenes/catálogo; lectura, captura y corrección
  de resultados. No recibe validación.
- Bioquímico: consulta de muestras/órdenes/catálogo/resultados y validación.
  No recibe captura ni corrección.

Los permisos los define `config/rbac.php` en la entrega de catálogo común de
ASII-02; CanonicalRole no concede permisos. Ambos cambios deben integrarse.
Las membresías siguen vinculadas al hospital y su revocación invalida el acceso.
No se asignan roles a personas ni se modifican tablas en esta entrega.

Esta ampliación no resuelve los acuerdos de esquema CENTRAL pendientes en #502
y #507 ni implica despliegue productivo de autenticación.
