# Micro-HIS Traslados, altas y liberación de camas (ASII-08)

PHP 8.2+ vanilla, sin framework ni Composer. Ejecuta «traslado o alta del paciente con liberación consistente de cama»
sobre la base HOSPITAL (`care_episodes`, `bed_stays` y `beds`).

| Capa | Contenido |
|---|---|
| `src/Domain` | Solicitudes, reglas (`ReglasLiberacion`), planes y resultados; sin dependencias |
| `src/Application` | Contrato `OperacionConLiberacionDeCama`, `ProtocoloLiberacion`, `TrasladoInterno`, `AltaHospitalaria` y los puertos `RegistroInternacion` y `Autorizador` |
| `src/Persistence` | `PdoRegistroInternacion` (PostgreSQL, sentencias preparadas y `FOR UPDATE`) y `AutorizadorReferenciaActiva` |
| `src/Presentation` | `ConsolaTraslados` (controller, sin SQL ni reglas) y `VistaResultado` (JSON y código de salida) |
| `bin/traslados.php` | Raíz de composición de consola |

## Pruebas

```bash
# Sin base de datos: dominio, aplicación con dobles, presentación y arquitectura
php micro-his/traslados-altas/tests/run.php

# Adaptador PDO contra una base HOSPITAL DESECHABLE ya migrada (el nombre debe terminar en _test)
TRASLADOS_PG_DSN="pgsql:host=127.0.0.1;port=55433;dbname=his_hospital_traslados_test" TRASLADOS_PG_PASSWORD=<clave local> \
  php micro-his/traslados-altas/tests/postgres.php
```

## Recorrido de consola con datos ficticios

1. Cree una base PostgreSQL desechable y mígrela con el esquema HOSPITAL del repositorio:
   `HOSPITAL_DB_DATABASE=<base> php artisan migrate --database=hospital --path=database/migrations/hospital`.
2. Cargue los datos ficticios una sola vez: `psql -d <base> -f micro-his/traslados-altas/database/demo.sql`.
3. Defina `TRASLADOS_PG_DSN`, `TRASLADOS_PG_PASSWORD` y `TRASLADOS_ACTOR_REF=00000000-0000-4000-8000-0000000000f1`, y ejecute:

```bash
php micro-his/traslados-altas/bin/traslados.php trasladar 00000000-0000-4000-8000-0000000000e1 00000000-0000-4000-8000-0000000000b3 "Aislamiento"   # 1: DESTINO_NO_DISPONIBLE
php micro-his/traslados-altas/bin/traslados.php trasladar 00000000-0000-4000-8000-0000000000e1 00000000-0000-4000-8000-0000000000b2 "Aislamiento"   # 0: éxito
php micro-his/traslados-altas/bin/traslados.php alta <estancia_destino_id del paso anterior>                                                      # 0: éxito
```

Códigos de salida: 0 éxito, 1 rechazo, 2 fallo sin cambios, 3 resultado incierto (consultar antes de reintentar), 64 solicitud inválida.

## Límites actuales

- La autorización solo exige un actor activo en `local_user_refs`; los permisos por rol dependen de RBAC (ASII-02).
- No hay API HTTP, tenant ni interfaz: se integran con Laravel en las semanas 16–17.
- Exigir `beds.status = available` en el destino es la decisión **propuesta** R-TA-01, pendiente con ASII-06. El flujo no cambia `beds.status`.
- La concurrencia con conexiones independientes todavía no está probada.
