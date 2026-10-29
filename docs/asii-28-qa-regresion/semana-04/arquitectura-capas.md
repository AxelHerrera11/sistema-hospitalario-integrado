# Capas, MVC y responsabilidades

| Capa / componentes implementados | Responsabilidad y objetos reutilizables |
|---|---|
| Presentation: CaseController | Valida tipos/campos de entrada; invoca DesignCase y traduce errores. Sin SQL ni regla de completitud. |
| Presentation: CaseView | Vista JSON del ManualCase creado; no conoce filas PDO. |
| Application: DesignCase | Genera identidad opaca, construye entidad y coordina save por CaseRepository inyectado. No conoce PDO. |
| Domain: ManualCase | Objeto readonly; normaliza espacios exteriores y exige identidad, propósito, pasos y esperado no vacíos. Pasos es texto que puede contener varias líneas. |
| Domain: CaseRepository / StorageFailure | Puerto de guardar/recuperar y error de almacenamiento común. |
| Persistence: InMemoryCaseRepository / PdoCaseRepository | Implementan el mismo puerto; almacenan/recuperan sin redefinir completitud. |
| demo.php / bootstrap.php | Composición CLI, autoload local y PDO SQLite en memoria; no cambian providers ni configuración compartida. |

MVC: el controlador recibe; el modelo es dominio más coordinación/puerto de
aplicación; la vista produce JSON. No se confunde MVC con tres carpetas nuevas:
sus responsabilidades se distribuyen entre las cuatro capas anteriores.
Dependencias: Presentation → Application → Domain; Persistence → Domain;
CaseView → Domain. Domain no importa Application, PDO, HTTP ni framework.
demo.php compone por constructor; sin ciclos ni localizador global de servicios.

Flujo RF-01: JSON → validación de formato → DesignCase → ManualCase válido →
save → vista 201. Tipo/campo ausente: 400; texto vacío: 422 desde regla del
dominio; fallo de save: 503 sin exponer PDO. Son códigos del resultado CLI,
**no endpoints HTTP publicados**. JSON mal formado se rechaza en la entrada demo.

El patrón Repository encapsula almacenamiento y la inyección permite cambiarlo
sin modificar controlador/caso de uso. La única sentencia DDL está en el esquema
aislado cargado por demo/tests; el adaptador no crea tablas ni ejecuta migraciones.
Cada alta es un INSERT: no se inventa atomicidad multirregistro ni histórico de ejecuciones.
