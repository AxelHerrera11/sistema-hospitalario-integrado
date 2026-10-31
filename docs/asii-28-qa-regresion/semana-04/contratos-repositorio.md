# Contrato CaseRepository y LSP

| Operación | Precondición | Garantía / rechazo observable |
|---|---|---|
| save(ManualCase): void | Entidad válida con identidad no almacenada | Al terminar, find recupera todos sus valores. Una identidad repetida produce StorageFailure sin sobrescribir el original. |
| find(string): ?ManualCase | Identidad opaca, sin formato exclusivo de adaptador | Devuelve entidad equivalente o null si no existe; fallo de almacenamiento produce StorageFailure. |

InMemory conserva objetos readonly en un mapa por identidad. PDO usa INSERT
y SELECT con prepare/execute y parámetros para **todos** los valores; SQL y
nombres de tabla/columnas son constantes. Propósito con comillas o texto SQL
se conserva como dato, no como consulta. PDOException se traduce a StorageFailure
con causa interna; el controlador devuelve solo CASE_STORAGE_UNAVAILABLE.

El esquema SQLite de demostración usa qa_manual_cases con id PRIMARY KEY y
purpose/steps/expected NOT NULL. No es migración ni tabla existente del HIS;
cada conexión sqlite::memory: se destruye al terminar. Persistencia durable o
PostgreSQL requieren contrato de despliegue y validación futura, sin tocar la base hospitalaria.

La misma batería se ejecuta sin cambiar el consumidor para ambos adaptadores:
guardar/leer, segunda alta independiente, rechazo de campos vacíos, tipo inválido,
ausencia y duplicado sin pérdida. No se comparan identidades aleatorias entre
ejecuciones; se comparan garantías y datos. El doble de fallo simula StorageFailure;
PDO sin tabla prueba error real. Ningún adaptador exige credenciales/formato extra.
Esto aplica el criterio de sustitución de Semana 2 a este puerto acotado;
no demuestra RNF-04 del registro de ejecuciones todavía inexistente.
