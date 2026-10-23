# Límite Repository

Estado: `PROPUESTO`; el contrato y sus operaciones requieren revisión junto
con el caso de uso.

## Responsabilidades

- Definir el límite de lectura/escritura de prescripciones sin acoplar la
  aplicación a SQL o a una conexión concreta.
- Ofrecer adaptadores PDO e InMemory con semántica observable equivalente para
  las operaciones que apruebe el contrato.
- Distinguir ausencia de registro de falla de persistencia.
- Convertir `PDOException` en `PersistenceFailure` en el adaptador; no devolver
  éxito ni colección vacía ante una falla.
- Mantener las transiciones de estado y la regla de no guardar en rechazo bajo
  control del caso de uso y del dominio.

## Decisiones pendientes de validación

- Las operaciones y sus tipos exactos deben seguir al modelo de dominio
  integrado; este documento no inventa firmas.
- La conexión PDO será provista por el composition root, no creada por el
  controller ni por el dominio.
- Los detalles de tablas y columnas pertenecen a la migración y al esquema
  aprobados, no a este boceto.
- La consistencia transaccional se debe definir con el flujo de persistencia;
  no se afirma aquí que exista una transacción implementada.

InMemory es un adaptador de prueba, no una alternativa que se seleccione
silenciosamente ante un fallo de PDO.
