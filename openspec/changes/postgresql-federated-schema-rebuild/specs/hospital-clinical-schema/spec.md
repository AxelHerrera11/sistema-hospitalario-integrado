# Delta for Hospital Clinical Schema

## ADDED Requirements

### Requirement: Exact HOSPITAL MVP inventory
Each HOSPITAL database SHALL contain exactly these 27 entities: `local_user_ref`, `doctor_profile`, `specialty`, `doctor_specialty`, `local_patient`, `care_episode`, `care_assignment`, `appointment`, `ward`, `bed`, `bed_stay`, `medical_record`, `clinical_note`, `diagnosis`, `allergy`, `vital_sign`, `medication`, `prescription`, `lab_test`, `lab_order`, `lab_order_item`, `specimen`, `lab_result_version`, `critical_alert`, `local_access_audit`, `outbox_event`, `inbox_message`. UUIDs SHALL identify federated entities/events; all physical FKs SHALL remain local.

#### Scenario: Hospital inventory
- GIVEN a fresh HOSPITAL database
- WHEN catalog assertions run
- THEN exactly 27 listed entities exist and no FK references CENTRAL or another hospital database.

### Requirement: Clinical invariants and versioning
The schema SHALL enforce local patient/episode/record consistency, valid temporal ranges, indexed FKs, non-overlapping active appointments and bed stays using PostgreSQL GiST exclusion support, and one active medical record per patient. Signed notes and validated lab results SHALL be immutable; corrections SHALL create linked versions with reasons. Clinical evidence SHALL be retained, closed, cancelled, or superseded rather than cascaded away.

#### Scenario: Contradictory or overlapping clinical data
- GIVEN an appointment/bed stay overlaps an active conflicting interval, or a signed result is updated
- WHEN the write is attempted
- THEN PostgreSQL rejects it; a correction is accepted only as a new version.

### Requirement: Logical federation and integration
`local_user_ref`, `local_patient.global_id`, audits, outbox, and inbox SHALL use scalar UUID/logical references to CENTRAL or other hospitals and SHALL NOT imply credentials or offline authentication. Outbox events SHALL exclude full clinical payloads; inbox processing SHALL be idempotent on `(source_node_id,event_id)`.

#### Scenario: Offline reference and duplicate event
- GIVEN CENTRAL is unavailable and a local reference is present
- WHEN local clinical data or a duplicate inbox event is written
- THEN local constraints still operate, no credentials are accepted from the reference, and the duplicate is deduplicated.
