# Semana 5 — Persistencia clínica y frontera cliente-servidor

ASII-18 asignado a Mar-03, issue #6. Se sigue la matriz del [README](../../../README.md). Laravel implementa el módulo en app/Modules/Reception con autoload PSR-4; el micro PHP se conserva como ejercicio académico independiente.

## Estado integrado y alcance de este PR

Referencia de esta documentación: develop adb128d3, que integra #841 (dominio y caso de uso) y #692 (persistencia e integridad, head c53aa59d). #1315 añade únicamente documentación; no incorpora endpoints ni migraciones nuevas.

La migración 2026_08_11_000601_create_specimen_reception_tables.php crea specimens en la conexión hospital, con UUID, numeric y restricciones CHECK. Las referencias a local_patients, care_episodes y local_user_refs tienen índices y FK ON DELETE RESTRICT. lab_order_item_id es una referencia lógica indexada sin FK a órdenes en esta entrega.

HospitalReceptionRepository resuelve el PDO de la conexión hospital y delega en App\Modules\Reception\Persistence\PdoReceptionRepository. Esta clase es propia de Laravel y guarda muestra y evento en outbox_events dentro de la misma transacción. Laravel no carga el repositorio ni el dominio del micro PHP.

## Entregas posteriores de la pila

#693 incorpora ReceptionServiceProvider, ReceptionController y las rutas de recepción. #694 incorpora la migración de reception_order_items, OrderProjection y reception:import-order. Estos elementos todavía no pertenecen al develop de referencia: el contrato OpenAPI y el diagrama de frontera describen esas entregas posteriores, no endpoints disponibles por integrar #1315.

En #692, UnavailableOrders expresa la falta de un proveedor de órdenes mediante LAB_ORDER_UNAVAILABLE (503). La proyección de #694 es un adaptador local; el consumidor de un contrato remoto de ASII-16 y su disponibilidad siguen pendientes.

## Rollback y coordinación con Javier

Los tests usan Tests\TestCase::hospitalRollbackSteps($desde). El helper cuenta los archivos de migraciones HOSPITAL desde el prefijo indicado, inclusive, para revertir un lote en orden descendente. Así incorpora las migraciones posteriores sin mantener números fijos ni sumar pasos manualmente.

SpecimenIntegrityTest usa el prefijo 2026_08_11_000601; DoctorScheduleIntegrityTest usa 2026_08_11_000900. Los tests HospitalCapability, MedicalRecord, Medication y SchedulingAndBed usan respectivamente 000100, 000401, 000500 y 000300 (todos con fecha 2026_08_11). El cálculo no sustituye la ejecución de los tests y presupone el lote único preparado por la suite.

La coordinación de #692 define el slot 000601 y las referencias locales. Solo Yamilet revisa y mergea. La evidencia histórica y los resultados atribuidos a revisión humana se distinguen en [EVIDENCIA.md](EVIDENCIA.md).

## Límites y fuentes

SD-REC-01: los rangos de volumen y temperatura son supuestos técnicos pendientes de validación clínica.

[Evidencia](EVIDENCIA.md) · [IA](DECLARACION_IA.md) · [Diagrama](persistencia.svg) · [Fuente](fuentes/persistencia.mmd) · [Contrato OpenAPI de entregas posteriores](openapi.yaml) · [Frontera](frontera-microservicio.md).
