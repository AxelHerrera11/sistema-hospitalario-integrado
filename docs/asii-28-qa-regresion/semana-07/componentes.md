# Componentes e interfaces reales

Todos los componentes de la primera tabla están implementados en
`micro-his/qa-regresion/`; no dependen de Laravel ni de un servidor HTTP.

| Nombre / capa | Responsabilidad | Entrada → salida | Dependencias requeridas | Validación / errores |
|---|---|---|---|---|
| demo.php / composición | Leer STDIN, componer y emitir JSON | JSON → sobre status/body | bootstrap, PDO SQLite, esquema y CaseController | JSON mal formado → 400 INVALID_JSON; arranque sin PDO/esquema no está normalizado por controlador |
| CaseController / Presentation | Validar formato e invocar alta | mixed → array status/body | DesignCase, CaseView, StorageFailure | Campos ausentes/no string → 400 INVALID_INPUT; DomainException → 422 CASE_INCOMPLETE; StorageFailure → 503 CASE_STORAGE_UNAVAILABLE |
| CaseView / Presentation | Representación pública explícita | ManualCase → 201/body/data | ManualCase | Sin reglas ni SQL; proyecta purpose, steps, expected, id; errores de serialización externa no son manejados aquí |
| DesignCase / Application | Crear identidad/entidad y guardar | Tres strings → ManualCase guardado | CaseRepository, ManualCase; random_bytes de PHP | Propaga dominio/StorageFailure; fallo inesperado de entropía no tiene traducción específica |
| ManualCase / Domain | Invariante RF-01 y objeto readonly | id y tres strings → caso válido | PHP estándar | trim en los tres textos; id/texto vacío → DomainException; no aplica reglas de ejecución |
| CaseRepository / Domain | Puerto ofrecido al caso de uso | save(ManualCase): void; find(string): ?ManualCase | ManualCase, contrato StorageFailure | No permite sobrescribir identidad; ausente devuelve null; fallo de almacenamiento común |
| InMemoryCaseRepository / Persistence | Implementar puerto con mapa | Entidad/identidad → almacenamiento/lectura | CaseRepository, ManualCase, StorageFailure | Duplicado → StorageFailure; valores conservados; sin SQL |
| PdoCaseRepository / Persistence | Implementar puerto con SQL preparado | PDO + entidad/identidad → almacenamiento/lectura | CaseRepository, ManualCase, StorageFailure, PDO | ERRMODE_EXCEPTION; INSERT/SELECT parametrizados; PDOException traducida, causa interna preservada |

El autoload local de bootstrap.php resuelve clases de MicroHis\QaRegresion.
database/schema.sqlite.sql crea solo la tabla de demo/tests; no es una migración
del HIS. tests/run.php compone adaptadores y verifica garantías, no es caso de uso.
CaseRepository exige entidad válida al guardar, conserva datos al recuperar y
rechaza duplicado sin alterar el original. Ambos adaptadores ofrecen esa interfaz;
DesignCase requiere el puerto, no la clase concreta. No se agrega formato de ID
exclusivo a un sustituto; la suite usa identidades opacas y generación aleatoria.

## LSP y representación

Los errores del almacenamiento se expresan como StorageFailure; ninguna alternativa
puede devolver éxito perdiendo datos ni exigir pasos extra al consumidor.
El mapa readonly y PDO tienen distinta implementación, pero comparten batería de
aceptación. Esto demuestra sustitución para casos, no RNF-04 completo del registro
de ejecución propuesto en Semana 2. La salida explícita de CaseView separa forma
pública de estructura interna; no cambia las pre/postcondiciones de CaseRepository.

## Componentes propuestos, no implementados

| Componente futuro | Contrato pendiente / diferencia frente al núcleo |
|---|---|
| Cliente QA y adaptador REST | #1400 propone POST/GET manual-cases; faltan HTTP, lectura de aplicación, permisos y aislamiento; los sobres CLI no son respuestas de esas rutas |
| Campaña/asociación | RF-02; selección y pertenencia válidas por acordar; no existe repositorio/tabla de campañas |
| Registro de ejecución/evidencia | RF-03/04/05/08; observaciones, resultado, vínculos e historia inmutable; aún sin clases concretas |
| Defectos/trazabilidad | RF-06/07; defecto solo desde fallo; severidad, acceso y cadena de relaciones pendientes |

El diagrama distingue dependencias de código de composición/almacenamiento y
separa las propuestas visualmente. Domain no depende de Presentation, Application
ni Persistence; Application requiere Domain; ambos adaptadores implementan Domain.
