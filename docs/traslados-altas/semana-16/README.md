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
