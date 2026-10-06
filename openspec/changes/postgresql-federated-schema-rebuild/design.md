# Design: PostgreSQL Federated Schema Rebuild

## Technical Approach

Destructively replace the SQLite/tenant snapshot with physically distinct PostgreSQL 15+ CENTRAL and HOSPITAL databases. Named Laravel connections, connection-pinned migrations/models, local-only FKs, logical cross-database UUIDs, and PostgreSQL RED tests implement the four specs. Framework tables remain separately installable. APIs/UI/workflows, compatibility/backfill, dual writes, legacy preservation, and unresolved offline authentication remain excluded.

## Architecture Decisions

| Decision | Choice and rationale |
|---|---|
| Topology | `central` and `hospital` use `pgsql`; default `hospital`; names must differ. Physical ownership provides isolation. |
| Layout | `database/migrations/{central,hospital,framework}` with `$connection`; independent deployment/rollback requires separate histories. |
| Keys/references | Domain PKs accept supplied UUIDs or default `gen_random_uuid()`; local UUID FKs are indexed with explicit actions; cross-database UUIDs are commented scalars with no ORM relations. This avoids federated collisions and false consistency. |
| Integrity | `timestamptz`, text/JSONB/date checks, partial uniqueness, and HOSPITAL `[)` GiST exclusions via `btree_gist`; database enforcement protects concurrent writes without rigid PG enums. |
| Evidence | Audit and signed/validated evidence reject update/delete; corrections insert unique `supersedes_id` versions, preserving history. |

## Data Flow

```text
Laravel ─explicit→ CENTRAL: network/MPI/grants/inbox
   └────default─→ HOSPITAL: care/clinical/audit/outbox
HOSPITAL outbox ─logical UUID event→ CENTRAL inbox
```

No migration, transaction, FK, model relation, or implicit seeder spans databases.

## Migration and Delivery Slices

Every row is a stacked-to-main PR targeting `main`, includes RED/GREEN tests, and is ≤400 authored changed lines; split further rather than use an unapproved `size:exception`.

| Slice | Exact entities/work | Depends on | Rollback boundary |
|---|---|---|---|
| F | Connections, harness, migration runners, framework relocation, legacy wipe | — | Restore prior config/root snapshot |
| C1 | `network`, `hospital`, `central_user`, `hospital_membership` | F | Down C1 only |
| C2 | `mpi_patient`, `mpi_identifier`, `patient_hospital_link`, `identity_match_candidate` | C1 | Down C2 only |
| C3 | `patient_consent`, `cross_hospital_grant`, `cross_access_audit`, `break_glass_review` | C2 | Down C3 only |
| C4 | `sync_node`, `sync_checkpoint`, `central_inbox_message` | C1 | Down C4 only |
| H1 | `local_user_ref`, `doctor_profile`, `specialty`, `doctor_specialty` | F | Down H1 only |
| H2 | `local_patient`, `care_episode`, `care_assignment` | H1 | Down H2 only |
| H3 | `appointment`, `ward`, `bed`, `bed_stay` | H2 | Down H3 only |
| H4 | `medical_record`, `clinical_note`, `diagnosis`, `allergy`, `vital_sign` | H2 | Drop H4 triggers; down H4 |
| H5 | `medication`, `prescription` | H4 | Drop immutability trigger; down H5 |
| H6 | `lab_test`, `lab_order`, `lab_order_item`, `specimen` | H2 | Down H6 only |
| H7 | `lab_result_version`, `critical_alert` | H6 | Drop version trigger; down H7 |
| H8 | `local_access_audit`, `outbox_event`, `inbox_message` | H2 | Drop audit trigger; down H8 |
| MC/MH | CENTRAL models (2 slices) / HOSPITAL models (3 slices) | C1–C4 / H1–H8 | Revert only slice models/tests |
| SC/SH | CENTRAL / HOSPITAL factories and idempotent seeders | MC / MH | Revert seed code/data only |
| D | Exact inventory, operational docs, README | all prior | Revert docs/assertions only |

This maps exactly 15 CENTRAL and 27 HOSPITAL spec entities. Concrete paths are `database/migrations/{central,hospital,framework}/`, `app/Models/{Central,Hospital}/`, `database/factories/{Central,Hospital}/`, `database/seeders/*DatabaseSeeder.php`, `tests/Database/`, `docs/database/federated-postgresql-development.md`, plus modifications to `config/database.php`, `.env.example`, `phpunit.xml`, `composer.json`, and `README.md`.

## Interfaces / Contracts

`CentralModel`/`HospitalModel` set connection, string non-incrementing UUID keys, `HasUuids`, casts, and local relations. `local_user_ref`, patient links/global IDs, audits, grants, and inbox/outbox identifiers remain scalar logical references. `local_user_ref` stores no credential. Triggers/functions drop before dependent tables; `btree_gist` is never dropped.

## Testing Strategy

| Layer | Verification |
|---|---|
| Unit | Model connection/key/cast/relation metadata and factory states. |
| Integration | Serial `database-integrity` tests on two disposable PG15+ databases prove catalog inventory, isolation, FKs/indexes/checks/exclusions/triggers, logical-reference absence, immutability, versioning, and seed idempotency. Explicit scenarios: clean install; destructive wipe to empty owned schemas; independent down/up rollback; complete reinstall reproducing exact 15/27 inventories; induced mid-migration failure leaves no partial transaction artifacts, then repair/rerun succeeds. Ordinary tests transact on both connections; skips/SQLite never satisfy acceptance. |
| E2E | N/A—no API/UI boundary. |

## Threat Matrix

N/A — no routing, shell, subprocess, VCS/PR automation, executable classification, or process-integration boundary.

## Migration / Rollout

Preflight blocks unless Composer dependencies, both PHP extensions `pdo_pgsql` **and** `pgsql`, PostgreSQL 15+, two distinct disposable databases, and HOSPITAL `CREATE EXTENSION btree_gist` privilege are verified. Run F, C1→C4 and H1→H8, then optional framework tables. Before merge, rehearse clean install, destructive wipe, one-step/full down-up, reinstall, and induced-failure recovery independently on both databases. Never operate on shared databases or fall back to SQLite.

## Open Questions

- [ ] Offline authentication remains unresolved and outside this change; no persistence artifact may claim complete disconnected authentication.
