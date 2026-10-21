# ASII-14 - Catálogo de medicamentos - Semana 4

- **Tema:** capas, MVC y Repository sobre el MISMO Micro-HIS de Semana 3 (`micro-his/catalogo-medicamentos/`)
- **Flujo:** alta, búsqueda y control de vigencia de un medicamento
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165) · Código: PR #572 (Repository) y rama `feature/asii-14-semana-04-mvc-catalogo-medicamentos-yamilet1235` (MVC)

## Capas y entrada MVC

![MVC y Repository](./01-mvc-repository.svg)

[Fuente PlantUML](./fuentes/01-mvc-repository.puml)

| Capa / rol MVC | Componentes | Responsabilidad | No hace |
|---|---|---|---|
| Presentation · controlador frontal | `Router` | Elegir el controlador por método y ruta; validar solo la forma (objeto JSON, campos de texto) | SQL, reglas de negocio |
| Presentation · controladores | `RegistrationController`, `SearchController`, `ValidityController` | Llamar a su contrato y traducir el resultado a `{status, body}` | SQL, reglas de negocio |
| Presentation · vista | `JsonView` | Emitir el estado HTTP y el cuerpo JSON | Decidir resultados |
| Application | Casos de uso, contratos ISP, puertos y `MedicationRepository` | Orquestar alta, búsqueda y vigencia; declarar lo que necesita de la persistencia | Conocer PDO o HTTP |
| Domain · modelo | `Medication`, `Validity`, `MedicationRuleViolation` | Reglas de alta y de vigencia `active/inactive` | Depender de otras capas |
| Persistence | `PdoMedicationCatalog`, `InMemoryMedicationRepository` | Cumplir `MedicationRepository` | Reglas de negocio |
| Composición | `app.php`, `public/index.php`, `bin/catalogo.php` | Unir las piezas | Lógica propia |

`tests/architecture.php` comprueba estas fronteras: el Router no usa Domain, Persistence ni SQL; ningún caso de uso ni controlador depende del Repository completo.

## Repository en el límite apropiado

`MedicationRepository` vive en **Application**: el núcleo define qué necesita y Persistence lo implementa. Extiende los tres puertos de Semana 3, así que:

- Un adaptador nuevo implementa un único contrato y la suite `tests/repository.php` dice si lo cumple.
- Cada caso de uso sigue recibiendo solo su puerto: `ConsultMedications` no puede escribir (ISP de Semana 2).

## Objetos reutilizables

| Objeto | Reutilización |
|---|---|
| `MedicationRepository` y `tests/repository.php` | Contrato y prueba de contrato para cualquier adaptador futuro (por ejemplo, la tabla H5) |
| `InMemoryMedicationRepository` | Pruebas y demostraciones sin base de datos |
| `CatalogResponse` | Traducción única de errores esperados a 422, 409, 404 y 503 |
| `Request` y `JsonView` | Entrada y salida HTTP independientes del controlador |
| `MedicationLookup` | Contrato de solo lectura apto para consumidores como Prescripciones |

## Rutas HTTP del micro-HIS

| Método y ruta | Controlador | Éxito | Errores |
|---|---|---|---|
| `POST /medications` | `RegistrationController` | 201 | 400, 409, 422, 503 |
| `GET /medications?q=` | `SearchController::search` | 200 | 422, 503 |
| `GET /medications/{id}/validity` | `SearchController::validity` | 200 | 404, 503 |
| `PATCH /medications/{id}/validity` | `ValidityController` | 200 | 400, 404, 422, 503 |

Son las rutas locales del micro-HIS; el contrato API v1 se define en Semana 5.

## Documentos

- [Integración con un repositorio de datos compartido](./repositorio-compartido.md)
- [Evidencia de ejecución](./EVIDENCIA.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)
