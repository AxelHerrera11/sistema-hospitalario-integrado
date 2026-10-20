# Arquitectura C4/UML — Salas y camas

```mermaid
flowchart TD
  U[Usuario / Cliente] --> P[Presentation<br/>CatalogController]
  P --> A[Application<br/>CatalogService]
  A --> D[Domain<br/>Ward y Bed]
  A -. contratos WardRepository y BedRepository .-> C[Application<br/>Repositorios]
  W[Persistence<br/>PdoWardRepository] -. implementa .-> C
  B[Persistence<br/>PdoBedRepository] -. implementa .-> C
  W --> PG[(PostgreSQL<br/>wards)]
  B --> PG2[(PostgreSQL<br/>beds)]
  W --> D
  B --> D
```

`CatalogService` valida la existencia del ward y coordina altas; `Ward` y `Bed` validan sus estados. Persistence implementa los contratos de Application y concentra PDO, SQL parametrizado y la hidratación. Dependencias: `Presentation → Application → Domain`; `Persistence → Application` y `Persistence → Domain`.
