# Responsabilidades MVC

Estado: `PROPUESTO`; validar cuando la capa de entrada se implemente.

| Capa | Responsabilidad | No debe hacer |
|---|---|---|
| Controller | Adaptar la petición al caso de uso y mapear el resultado a una respuesta HTTP. | Ejecutar SQL o codificar reglas clínicas. |
| Application | Coordinar catálogo, alergias, dominio, persistencia y auditoría mediante contratos. | Depender de PDO, HTTP remoto o detalles del framework. |
| Domain | Mantener reglas e invariantes de prescripciones y severidad. | Conocer request/response, base de datos o framework. |
| Repository adapter | Leer y persistir por el contrato del repositorio seleccionado. | Decidir si un conflicto clínico debe aceptarse. |
| Composition root | Construir el caso de uso con sus adaptadores y configuración. | Ocultar errores de inicialización o producir implementaciones silenciosas. |

## Flujo propuesto

1. El controller valida forma y autorización de entrada y delega el caso de uso.
2. Application obtiene catálogo y alergias a través de sus puertos.
3. Domain evalúa las reglas de negocio y produce el resultado tipado.
4. Solo un resultado aceptado continúa al repositorio; un rechazo no guarda.
5. El controller traduce errores explícitos al contrato HTTP correspondiente.

La autenticación, RBAC, tenant y validación HTTP deben permanecer en la capa
de entrada cuando exista esa integración; esta propuesta no afirma que ya esté
disponible.
