# Arquitectura del catálogo de wards y beds

## Capas y responsabilidades

```text
Consumidor
    ↓
Presentation: CatalogController
    ↓
Application: CatalogService ── WardRepository / BedRepository
    ↓                                ↑
Domain: Ward, Bed                    │ implementan
                                     │
Persistence: PdoWardRepository / PdoBedRepository ── PostgreSQL
```

| Capa | Componentes reales | Responsabilidad |
|---|---|---|
| Domain | `Ward`, `Bed`, `DomainRuleViolation` | Representar el catálogo y rechazar campos obligatorios vacíos o estados fuera del dominio. No consulta base de datos ni decide ocupación. |
| Application | `CatalogService`, `WardRepository`, `BedRepository`, errores de aplicación | Coordinar el alta; resolver `wardCode` a `wardId` antes de crear la cama; definir contratos de lectura/escritura y errores esperables. |
| Persistence | `PdoWardRepository`, `PdoBedRepository`, `PersistenceFailure` | Ejecutar SQL PDO parametrizado, hidratar entidades, devolver IDs generados y traducir errores PDO. PostgreSQL aplica FK y restricciones UNIQUE/CHECK. |
| Presentation | `CatalogController` | Invocar casos de uso y representar éxito como 201, ward inexistente como 404, regla de dominio como 422 y fallo de persistencia como respuesta genérica 500. |

El `CatalogController` es un adaptador de presentación probado directamente;
no se identificó una ruta HTTP Laravel para ASII-06. El bootstrap del
micro-HIS registra autocarga PSR-4 propia.

## Contratos relevantes

- `WardRepository::findByCode(string): ?Ward` resuelve el identificador que
  requiere la cama; `insertWard(Ward): Ward` devuelve el ward con ID.
- `BedRepository::insertBed(Bed): Bed` persiste una cama que ya incluye
  `wardId` y devuelve el ID generado.
- `CatalogService::createWard(array): Ward` construye la entidad y delega el
  alta.
- `CatalogService::createBed(string $wardCode, array): Bed` busca el ward;
  devuelve `WARD_NOT_FOUND` si no existe y solo entonces crea/persiste la cama.

Los repositorios abstraen el esquema SQL; la persistencia no decide casos de
uso. La base exige `beds.ward_id`, referencia `wards.ward_id` con
`ON DELETE RESTRICT` y unicidad de `(ward_id, code)`. El código captura una
posible falla de carrera mediante el error de persistencia, sin sustituir la
restricción con una consulta previa de duplicados.

## Dependencias permitidas

- `Presentation → Application`.
- `Application → Domain` y contratos de repositorio definidos en Application.
- `Persistence → contratos de Application + Domain + PDO`.
- `Domain` no depende de Application, Persistence, Presentation ni Laravel.

`CatalogService` recibe ambas interfaces por constructor (DIP). El fake de
`tests/run.php` puede sustituirlas para probar el flujo; ese test no acredita
las restricciones reales de PostgreSQL ni una implementación InMemory de
producción.

## SOLID en el código existente

| Principio | Aplicación específica | Límite/evidencia |
|---|---|---|
| **SRP** | `Ward` y `Bed` validan sus invariantes; `CatalogService` coordina; cada repositorio PDO maneja una entidad; `CatalogController` traduce respuestas. | La separación evita mezclar SQL, política del dominio y respuesta de presentación. |
| **OCP** | El flujo depende de `WardRepository` y `BedRepository`; una implementación alternativa puede incorporarse detrás de esos contratos. | Existe el adaptador PDO y un fake de prueba; no se afirma que haya otros adapters operativos. |
| **LSP** | `PdoWardRepository` y `PdoBedRepository` implementan los contratos; el fake permite ejercitar el consumidor sin PDO. | LSP aplica al intercambio de implementaciones que respeten los resultados y errores del contrato. El fake simplificado no demuestra por sí mismo las garantías de FK/UNIQUE. |
| **ISP** | Ward y Bed tienen interfaces distintas con operaciones específicas, en vez de un repositorio genérico con métodos ajenos a cada entidad. | `BedRepository` solo expone alta; no se añaden métodos no requeridos por el flujo implementado. |
| **DIP** | `CatalogService` depende de las abstracciones de Application, no de `PdoWardRepository`, `PdoBedRepository` ni de SQL. | El adapter concreto se entrega en composición externa; la prueba inyecta el fake. |

## Límites e integración

- **Ocupación/admisión:** la migración también define `bed_stays` y
  `care_episodes`; el catálogo no crea estancias ni decide disponibilidad
  temporal. Esos casos corresponden a los módulos de ocupación/admisión.
- **Traslados/altas:** pueden consultar `beds` y registrar cambios en
  `bed_stays`; no trasladar esa lógica a `CatalogService`.
- **Estado operativo:** `beds.status` admite `available`, `occupied`,
  `maintenance` e `inactive`; `wards.status` admite `active` e `inactive`.
  El dominio valida los valores, pero no define transiciones, ni sincroniza
  `occupied` con `bed_stays`.
- **Ward inactivo:** el caso de uso actual verifica existencia, no estado
  activo. No se incorpora una regla adicional hasta que el requisito lo
  especifique.
- **Persistencia:** el adapter usa PDO; no se declara probada contra PostgreSQL
  por la suite actual, que utiliza un fake.
