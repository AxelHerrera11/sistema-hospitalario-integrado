# Servicio común de auditoría

Relacionado con el issue #9 (ASII-07).

## Uso

El servicio `App\Services\AuditLogger` permite insertar registros
en `audit_logs` desde cualquier módulo.

Firma del método:

```php
public function log(
    string $action,
    Model $entity,
    ?array $oldValues = null,
    ?array $newValues = null,
    ?User $user = null
): AuditLog
```

La entidad debe estar guardada, tener un ID numérico y pertenecer
a un hospital mediante `tenant_id`.

Ejemplo dentro de un controlador, con `$patient` ya guardado:

```php
use App\Services\AuditLogger;

app(AuditLogger::class)->log(
    action: 'viewed',
    entity: $patient
);
```

Para registrar cambios, enviar únicamente los campos necesarios
en `oldValues` y `newValues`. No incluir contraseñas ni tokens.

## Usuario y hospital

Si no se proporciona `user`, se utiliza el usuario autenticado
con el guard `api`.

En seeders o comandos se puede proporcionar el usuario
explícitamente mediante el argumento `user`.

El servicio rechaza entidades de un hospital distinto al hospital
actual y usuarios de un hospital distinto al de la entidad.
Si no hay usuario autenticado ni explícito, `user_id` queda vacío.

El hospital del registro se obtiene de la entidad.

## Comportamiento

Registra acción, entidad, ID, usuario, hospital y valores anteriores
y nuevos. También toma la IP y el agente del navegador de la
petición disponible.

El servicio solo inserta registros. Cada módulo debe llamarlo
en las operaciones que necesite auditar; no registra automáticamente
todas las acciones.

Cuando una operación requiera guardar sus cambios y la auditoría
juntos, ambas llamadas deben ejecutarse dentro de la misma
transacción de base de datos.

## Datos demo de laboratorio

El seeder incluye:

- Un usuario `bioq+<slug-del-hospital>@demo.local`, rol `Bioquimico`
  y contraseña demo `password`.
- Órdenes pendientes, en proceso y completadas.
- Resultados ingresados por el técnico y validados por el bioquímico.
- Clasificación con límites críticos inferiores y superiores,
  ignorando los límites vacíos.
- Fechas de confirmación consistentes con el estado de las alertas.

Estos cambios se aplican al generar los datos demo; no actualizan
automáticamente los registros que ya existen.

## Pruebas

Ejecutar con la base `hospital_his_test` configurada:

```powershell
php vendor/phpunit/phpunit/phpunit --configuration phpunit.pgsql.xml
```

Las pruebas `AuditLoggerTest` y `DemoLaboratorySeederTest`
comprueban el servicio y la consistencia de los datos demo.