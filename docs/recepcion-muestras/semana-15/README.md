# Semana 15 — Pruebas y trazabilidad funcional

Implementación real en Laravel 12 y Vue 3. El runtime no carga micro-his. Las semanas 1–4 conservan sus entregas mergeadas; las semanas 5–15 continúan en los PR existentes.

| Criterio | Evidencia ejecutable |
|---|---|
| Reglas de identificación y calidad | tests/Unit/Reception/ReceiveSpecimenTest.php |
| Recepción, rechazo, consulta y etiqueta | tests/Feature/Reception/ReceptionApiTest.php |
| JWT, RBAC y actor clínico | ReceptionApiTest y ReceptionContractTest |
| Atomicidad y concurrencia PostgreSQL | ReceptionIntegrityTest |
| UUID, referencias y restricciones | SpecimenIntegrityTest |
| Pendientes, cancelación y custodia inmutable | ReceptionFlowTest |
| Responsive, accesibilidad y recuperación | tests/Browser/Reception/run.cjs |

Identidad de las pruebas HTTP: CENTRAL SQLite; datos clínicos: PostgreSQL real, esquemas aislados. El navegador usa dobles HTTP para comprobar el componente; esas pruebas no sustituyen las de API.

Operación: configurar RECEPTION_HOSPITAL_ID para la base HOSPITAL de este despliegue, sincronizar local_user_refs y aplicar migraciones hospitalarias. Importar órdenes confiables con reception:import-order archivo.json. Entrar en /recepcion-muestras con permisos muestras.*. outbox_events queda disponible para el publicador institucional.

La pila se revisa en orden: #841 → #692 → #693 → #694 → #695 → #696 → #697 → #698 → #699 → #700 → #701 → #702 → #839. No ejecutar merges automáticos.
