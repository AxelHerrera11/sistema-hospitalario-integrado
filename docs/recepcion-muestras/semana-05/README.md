# Semana 5 — Persistencia clínica y frontera cliente-servidor

ASII-18 asignado por el grupo a Mar-03, issue #6. Se sigue la matriz del [README](../../../README.md): el mismo micro evoluciona a cliente-servidor; la implementación vigente de Laravel es independiente (app/Modules/Reception, autoload PSR-4) y no carga el micro; el micro se conserva como ejercicio académico.

## Implementación de esta parte

La migración 2026_08_11_000601_create_specimen_reception_tables.php crea únicamente specimens en la conexión hospital. UUID identifica muestra, orden, paciente, episodio, receptor y solicitud. No existe tenant_id: X-Tenant-ID seleccionará la conexión en la parte HTTP.

Las tres referencias locales tienen FK ON DELETE RESTRICT e índices. lab_order_item_id tiene índice y comentario de coordinación, pero no FK ni tabla de órdenes: ASII-16 aún no publica 000600. volumen/temperatura usan numeric, rangos CHECK; estado/código/solicitud tienen restricciones verificadas.

PdoReceptionRepository del micro se usa también con PDO de Laravel. Guarda muestra y evento UUID en outbox_events dentro de la misma transacción. El esquema clínico usa la tabla común de 000700, sin reception_outbox ni tablas reception_*.

## Separación por tamaño y dependencias

Este PR cubre migración e integridad. La parte 2 incorpora provider, controlador inyectado, FormRequest, Resource, rutas y contrato comprobado con route:list. #841 incorpora el transporte HTTP independiente del micro. Los dos transportes reutilizan el mismo dominio.

Las órdenes pendientes y su consulta permanecen PROPUESTAS; el adaptador clínico responderá 503 LAB_ORDER_UNAVAILABLE hasta tener un contrato real. No se construye infraestructura de producción. La comunicación externa y su disponibilidad no quedan acreditadas por dobles.

## Rollback y coordinación con Javier

Con develop 80ab28de, 000601 se sitúa antes de 000700 y 000900. Los pasos hasta incluir la migración propia son HospitalCapability 9, MedicalRecord 5, Medication 4, SchedulingAndBed 7 y DoctorSchedule 1. DoctorSchedule no cambia porque 000601 queda antes de su migración; el ajuste no se calcula agregando uno al final indiscriminadamente. Se conserva un commit por contador cambiado y se ejecutan los cinco tests.

Si entra otra migración antes de integrar, quien integre después recalculará a partir de migrate:status. Solo Yamilet revisa y mergea.

## Límites y fuentes

SD-REC-01: los rangos técnicos de volumen y temperatura son supuestos pendientes de validación clínica, no una política hospitalaria aprobada. El esquema del micro es un ejercicio aislado; las FK clínicas se prueban aquí.

[Evidencia](EVIDENCIA.md) · [IA](DECLARACION_IA.md) · [Diagrama](persistencia.svg) · [Fuente](fuentes/persistencia.mmd). La petición de Javier en #692 guía el slot, las claves locales y la propiedad de las tablas.

[Contrato OpenAPI](openapi.yaml) · [Frontera de microservicio](frontera-microservicio.md) · [Diagrama](fuentes/frontera.mmd)
