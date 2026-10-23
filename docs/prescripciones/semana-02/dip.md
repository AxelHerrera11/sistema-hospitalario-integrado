# Inversión de dependencias y puertos

## Dirección de dependencias

La aplicación coordina el caso de uso a través de interfaces. Adaptadores
externos implementan esos contratos; el dominio no conoce PDO, HTTP, el
framework ni el contenedor. Los siguientes nombres y firmas corresponden a
contratos de aplicación en evaluación y no implican que estén ya integrados en
`develop`.

Los contratos de puertos relacionados están **en revisión en #900**. Este
documento describe la propuesta y no afirma que el PR esté integrado en
`develop`.

| Puerto | Contrato | Propósito |
|---|---|---|
| `MedicationCatalog` | `find(string $medicationId): MedicationSnapshot` | Leer el catálogo; diferenciar medicamento no encontrado/inactivo de falla de persistencia. |
| `AllergyChecker` | `activeAllergies(string $patientId): array` (`list<Allergy>`) | Obtener alergias activas; informar indisponibilidad explícitamente. |
| `AuditLogger` | `record(string $eventName, array $payload): void` | Enviar eventos al límite de auditoría; informar indisponibilidad explícitamente. |

`PrescriptionRepository` y `PrescriptionService` permanecen `PROPUESTO`: sus
contratos e implementación no se consideran integrados por esta documentación.

## Adaptadores

- Los dobles InMemory sirven para pruebas deterministas.
- Las implementaciones de producción (PDO o integración remota, según la
  dependencia) pertenecen a infraestructura y se especifican en sus tareas.
- Las excepciones de puerto no se convierten en listas vacías ni en éxitos
  aparentes.

## Límites

No se define aquí un puerto de persistencia de prescripciones: ese componente
se especificará y validará en su entrega de persistencia. La excepción de
alergia no constituye un puerto ni un endpoint aprobado.
