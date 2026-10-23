# Notas SOAP — Semana 16: adaptadores PDO contra PostgreSQL real

Hasta ahora los adaptadores PDO solo se habían ejecutado contra un esquema SQLite simplificado, cuya cabecera anotaba las divergencias como pendientes. El 2026-10-22 se ejecutaron contra las migraciones de `develop` (`database/migrations/hospital`) en PostgreSQL 16 y fallaron tres cosas.

## Antes y después

| Adaptador | Antes (código de #129 y #376) | Ahora |
|---|---|---|
| `PdoCareAssignmentReader` | `column "user_ref_id" does not exist` | Une `care_assignments` con `doctor_profiles.local_user_ref_id`; exige `status = 'active'`, `valid_from <= ahora` y `valid_to IS NULL`, como el trigger de `clinical_notes` |
| `PdoClinicalNoteStore::add` | El trigger `clinical_notes_record_episode_patient_trigger` rechazaba el INSERT: faltaban `medical_record_id` y `version_no` | Busca el expediente **abierto** del paciente del episodio y envía `version_no = 1`; sin expediente responde `409 MEDICAL_RECORD_NOT_FOUND` y no guarda nada. No crea expedientes: eso es de Expediente |
| `PdoIcd10Catalog` | `relation "icd10_codes" does not exist` | **Sin cambio:** la tabla no existe en `develop` ni en ninguna rama remota; falla segura con `503 ICD10_UNAVAILABLE` |

La auditoría (`local_access_audits`, slice H8, #581) funcionaba con el esquema real: el INSERT es compatible y la prueba comprueba que el `metadata` no contiene texto clínico.

## Cómo se ejecutó

```bash
docker run -d --name asii11-pg -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=his_hospital_test -p 55443:5432 postgres:16
HOSPITAL_DB_PORT=55443 HOSPITAL_DB_DATABASE=his_hospital_test HOSPITAL_DB_USERNAME=postgres HOSPITAL_DB_PASSWORD=postgres \
  php -d extension=pdo_pgsql vendor/bin/phpunit tests/Database/Hospital/NotasSoapPostgresTest.php   # 6 pruebas, 21 aserciones
```

Las suites de `micro-his/notas-soap` (SQLite) siguen en verde con el esquema alineado (`care_assignments` por `doctor_profile_id`, `medical_records`, `medical_record_id`), y las 24 pruebas Feature de Notas SOAP y del catálogo de medicamentos no cambian.

## Hallazgos que no son de este módulo

| # | Hallazgo | Prueba | Quién |
|---|---|---|---|
| R-06 | `clinical_notes_record_version_unique (medical_record_id, version_no)` junto con el trigger que exige `version_no = 1` sin `supersedes_id` permite **una sola nota SOAP por expediente**; la segunda falla con `23505` | `test_known_limit_r06_…` | Javier (ASII-09) |
| D-02 | `enforce_diagnosis_integrity` no comprueba que la nota esté sin firmar. **Mitigado en el módulo:** `addDiagnosis` relee el estado dentro de la transacción con `FOR SHARE` y responde `409 NOTE_SIGNED` sin guardar ni auditar (`test_a_note_signed_between_read_and_write_…`). **Sigue abierto en la base:** un INSERT directo en `diagnoses` se salta la mitigación (`test_known_defect_d02_…`); falta el trigger | Javier (ASII-09) |
| CIE-10 | No existe `icd10_codes`; hace falta una entidad nueva | — | Por decidir con Javier |

Las dos pruebas «known» documentan el comportamiento actual de la base: cuando Javier lo corrija, deben invertirse. La mitigación de D-02 vive en mi adaptador y no sustituye el trigger.
