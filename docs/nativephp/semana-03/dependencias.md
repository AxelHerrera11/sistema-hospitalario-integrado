# Dependencias del modulo

## Dependencias internas

| Capa | Depende de | No debe depender de |
| --- | --- | --- |
| Presentation | Application | SQL, PDO, reglas de dominio |
| Application | Domain, puertos | Detalles de base de datos, NativePHP real o HTTP |
| Domain | PHP puro | Application, Persistence, Presentation |
| Persistence | Puertos de Application, PDO | Controladores o reglas de UI |

## Contratos

### LocalActionRepository

Puerto para guardar y consultar acciones locales pendientes.

```php
public function save(LocalHisAction $action): void;
public function pendingForTenant(string $tenantId): array;
```

### SyncGateway

Puerto para simular envio a un backend HIS.

```php
public function push(LocalHisAction $action): bool;
```

### TelemetryRecorder

Puerto para registrar hallazgos tecnicos y eventos de sincronizacion.

```php
public function record(array $event): void;
```

## Riesgos controlados

- Datos clinicos: el prototipo usa payloads ficticios y minimizados.
- Tenant: la cola filtra por `tenantId`.
- Offline: `SyncPolicy` bloquea sincronizacion sin conectividad.
- Persistencia: PDO usa parametros preparados.
- Infraestructura: `FakeSyncGateway` evita servicios reales.

## Limites de Semana 3

- No instala NativePHP ni genera binarios.
- No publica instaladores de escritorio/movil.
- No consume APIs productivas del HIS.
- No almacena credenciales reales.
