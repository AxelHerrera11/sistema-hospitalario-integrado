# Dependencias del modulo

## Dependencias internas

| Capa | Depende de | No debe depender de |
| --- | --- | --- |
| Presentation | Application | SQL, PDO, reglas de dominio |
| Application | Domain, puertos | Detalles de base de datos o HTTP |
| Domain | PHP puro | Application, Persistence, Presentation |
| Persistence | Puertos de Application, PDO | Controladores o reglas de UI |

## Contratos

### PendingResultRepository

Puerto para consultar y guardar resultados pendientes.

```php
public function getById(string $tenantId, string $resultId): PendingLabResult;
public function save(PendingLabResult $result): void;
```

### AuditLogger

Puerto para registrar eventos de captura y correccion.

```php
public function record(array $event): void;
```

## Riesgos controlados

- Datos clinicos: el prototipo usa identificadores ficticios y no incluye informacion real.
- Tenant: cada busqueda de resultado recibe `tenantId`.
- Persistencia: PDO usa parametros preparados.
- Pruebas: el caso de uso se prueba con dobles y con repositorio InMemory.

## Limites de Semana 3

- No se integra al router Laravel.
- No crea tablas reales en PostgreSQL.
- No implementa autenticacion real.
- No expone API productiva.
