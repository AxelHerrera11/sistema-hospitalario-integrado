# Evidencia de la integración — Semana 16 (21/10/2026)

Entorno **local de verificación**: Windows 11, PHP 8.2.12, Laravel 12, PostgreSQL 17.10 en clústeres desechables propios (CENTRAL en 55431, HOSPITAL en 55433) y datos ficticios. No es staging ni on-premise.

| Prueba | Qué es real | Qué se simula | Resultado |
|---|---|---|---|
| `tests/Feature/TrasladosAltas/TrasladosAltasApiTest.php` | Middlewares, RBAC y controller | Identidad; HOSPITAL en memoria; activación de permisos dentro de la prueba | 3/3 |
| `tests/Database/Hospital/TrasladosAltasPostgresTest.php` | Además, PostgreSQL y el resolutor real | Identidad; activación dentro de la prueba | 3/3 |
| [`recorrido-api.sh`](./evidencia/recorrido-api.sh), fase «pendiente» | JWT, tenant, RBAC, auditoría CENTRAL y PostgreSQL | Nada | 4/4: todo denegado mientras los permisos estén en pending |
| `recorrido-api.sh`, fase «activada» | Lo mismo | **Activación de los permisos en el árbol de trabajo**, restaurada sin versionar | 7/7: traslado 201 y alta 200 persistidos |

Salida completa: [salida-recorrido-api.txt](./evidencia/salida-recorrido-api.txt).

## Lo que esta evidencia NO demuestra

- Que la operación funcione en el sistema integrado sin intervención: hasta que RBAC active los permisos, **todas las operaciones reales responden 403**.
- La sincronización de `local_user_refs`: las referencias del recorrido se sembraron a mano.
- El aislamiento físico entre hospitales: hay una sola base HOSPITAL; el 403 por tenant cruzado es un control de identidad.

## Incidente del entorno

Al ejecutar la suite `Database` con la base CENTRAL de pruebas apuntando a `his_central_smoke`, un `migrate:fresh` la vació. Era una base desechable propia, con datos ficticios, del entorno local de revisión. Se volvió a sembrar con los mismos identificadores antes del recorrido. Desde entonces, la suite `Database` se ejecuta solo con una CENTRAL de pruebas aparte.
