# Semana 16 - Integracion y primera entrega funcional

La entrega integra traslados y altas en el Micro-HIS compartido: SPA Vue -> API v1 Laravel -> autenticacion, tenant y RBAC -> aplicacion del micro-HIS -> PostgreSQL HOSPITAL.

| Parte | Evidencia | Estado |
|---|---|---|
| API y dependencias | [integracion-api.md](./integracion-api.md) | Integrada |
| Permisos | [permisos-propuestos.md](./permisos-propuestos.md) | Pendientes de aprobacion de ASII-02 |
| Recorrido HTTP y PostgreSQL | [evidencia-integracion.md](./evidencia-integracion.md) | Verificado localmente |
| Interfaz SPA | [`evidencia/salida-spa.txt`](./evidencia/salida-spa.txt) y [`capturas/`](./capturas/) | Verificada localmente |

La evidencia real confirma traslado y alta persistidos, continuidad de estancias, cierre conjunto de estancia y episodio, errores y permisos. La activacion RBAC del camino exitoso fue temporal y esta identificada como simulacion; la configuracion integrada continua denegando los tres permisos pendientes.

No se declara despliegue en staging ni sincronizacion automatica de `local_user_refs`. Esas dependencias, con responsables y acciones, estan registradas en [integracion-api.md](./integracion-api.md#lo-que-depende-de-otros-modulos).

[Declaracion de uso de IA](./DECLARACION_IA.md)

## Actualización del 30/10/2026

| Tema | Estado |
|---|---|
| Acceso entre hospitales | Corregido (#1561): un miembro válido de otro hospital recibe 403 `HOSPITAL_DATABASE_TENANT_MISMATCH` y no cambian filas. Requiere `HOSPITAL_DB_TENANT_ID` |
| Permisos | Propuesta lista en #1577, con pruebas sobre la matriz real. **No integrada:** la decide RBAC (ASII-02). Hasta entonces, todo 403 y sin entrada en el menú |
| Identidad local | Sin cambio: ningún módulo crea `local_user_refs`. Médicos, Expediente y Recepción solo las leen, igual que este módulo. Se siembran a mano en la instalación local |
| Servidor, staging u on-premise | No hay entorno autorizado |

### Errores corregidos

| Error | Corrección | PR |
|---|---|---|
| Menú sin la entrada del módulo (D8) | Entrada con permiso | #1108 |
| Aviso falso al cerrar sesión con token vencido | Política de cierre de sesión | #1113 |
| Cruce entre hospitales | Guard de base | #1561 |
| El 422 mostraba la sintaxis de la consola | La ayuda pasa a la consola | Entrega de esta fecha |

