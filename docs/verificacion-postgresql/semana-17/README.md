# Semana 17 — Seguridad y despliegue de la verificación PostgreSQL

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL (ASII-09)
- **Issue:** `Refs #36`

## Alcance

Se verifica que el esquema HOSPITAL no se trate como un conjunto de archivos solamente:
las migraciones de los slices existentes deben estar identificadas, el tenant debe aislar
la conexión y una indisponibilidad de PostgreSQL debe responder `503` sin exponer SQLSTATE.

## Evidencia reproducible

```bash
php docs/verificacion-postgresql/semana-16/verificar_integracion.php
php artisan migrate:status --database=hospital
php artisan test tests/Database/Hospital --no-coverage
```

El primer comando comprueba el contrato y los archivos de migración. Los dos últimos deben
ejecutarse con una conexión PostgreSQL de prueba; si el entorno no tiene esa conexión, la
salida se registra como bloqueo de entorno, no como aprobación.

## Controles de seguridad

- La conexión HOSPITAL se selecciona por el tenant autenticado.
- Las rutas de ocupación y laboratorio exigen JWT, tenant y RBAC.
- La evidencia no contiene credenciales ni datos clínicos reales.
- Los errores de indisponibilidad se traducen a `503` y no devuelven la excepción interna.

## Criterio de cierre

La semana queda acreditada cuando los tres comandos se ejecutan en PostgreSQL de prueba,
la lista de migraciones coincide con el esquema y las pruebas muestran aislamiento y
respuesta segura ante caída de la base.
