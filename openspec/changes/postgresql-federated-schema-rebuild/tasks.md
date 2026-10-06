# Tasks: PostgreSQL Federated Schema Rebuild

## Review Workload Forecast

| Field | Value |
|---|---|
| Estimated changed lines | 6,250–7,770 total; 240–390 per slice |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | PRs 1–21, stacked to `main`, each autonomous |
| Delivery strategy | force-chained |
| Operational delivery | automatic chaining |
| Chain strategy | stacked-to-main |

Decision needed before apply: No
Chained PRs recommended: Yes
Chain strategy: stacked-to-main
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Focused test command | Runtime harness | Rollback boundary |
|---|---|---|---|---|---|
| 1 | Prerequisites, topology, harness | PR 1 | `composer test -- --group=database-integrity` | Two disposable PG15+ DBs; `pdo_pgsql`, `pgsql`, `btree_gist` | Revert config/harness and dependency changes |
| 2–21 | One schema/model/seed/docs slice | PR 2–21 | Slice-specific `php artisan test ...` | Same two disposable DBs; no SQLite | Revert that slice’s files and down migration |

Every PR uses force-chained, automatic chaining with the `stacked-to-main` strategy, is ≤400 authored changed lines, names its predecessor, and excludes controllers/APIs/views/modules. Each task is RED → GREEN → REFACTOR; acceptance requires the focused command passing without skips.

## Phase 1: Prerequisite and Foundation

- [x] 1.1 RED: add `tests/Database/ConnectionIsolationTest.php` and `MigrationLayoutTest.php`; run `composer test -- --group=database-integrity` and observe missing Composer/PG prerequisites.
- [x] 1.2 GREEN: install Composer dependencies; require/verify PostgreSQL 15+, `pdo_pgsql`, `pgsql`, two distinct disposable CENTRAL/HOSPITAL test DBs, and HOSPITAL `btree_gist`; update `composer.json`, `.env.example`, `phpunit.xml`, `config/database.php`, `tests/TestCase.php`, and dual-DB helpers.
- [x] 1.3 GREEN: relocate framework migrations, delete incompatible root domain migrations/models/factories/seeders, replace `database/seeders/DatabaseSeeder.php`; verify no SQLite acceptance path.
- [x] 1.4 REFACTOR/accept: run `php artisan test --group=database-integrity --filter='ConnectionIsolation|MigrationLayout'` and `vendor/bin/pint --test`; rollback by restoring foundation/config and moved framework files only.

## Phase 2: CENTRAL Migrations (PRs 2–5)

- [x] 2.1 RED/GREEN/REFACTOR: `tests/Database/Central/NetworkIdentityIntegrityTest.php` then `database/migrations/central/2026_08_11_000000_create_network_registry.php` and `000100_create_identity_and_memberships.php`; prove four tables, UUIDs, local FK indexes, checks and partial uniques; `php artisan test tests/Database/Central/NetworkIdentityIntegrityTest.php`; down/up is rollback.
- [x] 2.2 RED/GREEN/REFACTOR: `MpiIntegrityTest.php` then central `000200_create_mpi_tables.php`; prove canonical `mpi_identifiers`/`identity_match_candidates`, identifier/link uniqueness, score bounds, merge/link status-temporal coherence, self-merge rejection and logical cross-DB UUIDs; focused test and one-step down/up.
- [x] 2.3 RED/GREEN/REFACTOR: `ConsentGrantIntegrityTest.php`, `CrossAccessAuditImmutabilityTest.php` then `000300_create_consent_and_access_control.php`; prove JSONB/period/evidence checks and append-only trigger; focused tests and trigger-first down.
- [x] 2.4 RED/GREEN/REFACTOR: `CentralSyncIntegrityTest.php` then `000400_create_central_sync_tables.php`; prove idempotency, payload shape, nonnegative counters and state timestamps; focused test and rollback preserving prior CENTRAL tables.

## Phase 3: HOSPITAL Migrations (PRs 6–12)

- [x] 3.1 RED/GREEN/REFACTOR: capability/professional tests then hospital `000000_enable_hospital_extensions.php` and `000100_create_local_identity_and_professional_tables.php`; prove PG15+/`btree_gist`, UUIDs, no credentials, indexes and uniqueness; focused tests; never drop extension.
- [x] 3.2 RED/GREEN/REFACTOR: `CareIntegrityTest.php` then `000200_create_patient_and_care_tables.php`; prove patient/episode/assignment checks, indexes and no CENTRAL FK; focused test and one-step down/up.
- [x] 3.3 RED/GREEN/REFACTOR: `SchedulingAndBedIntegrityTest.php` then `000300_create_scheduling_and_bed_tables.php`; prove `[)` adjacent intervals succeed and overlapping appointments/stays fail; focused test and exclusion-constraint rollback.
- [x] 3.4 RED/GREEN/REFACTOR: `MedicalRecordIntegrityTest.php` then H4 `000400`/`000401` migrations; prove record, note, diagnosis, allergy and vital constraints; focused test and coherent down.
  - [x] PR9a: `medical_records`, `clinical_notes`, and `diagnoses`, including local consistency, signed-note append-only correction, coding/status/date constraints, and rollback proof.
  - [x] PR9b corrective rerun verified: `allergies.reaction` and append-only `vital_signs` correction lineage, plus temporal/severity/range constraints and the remaining H4 inventory.
- [ ] 3.5 RED/GREEN/REFACTOR: `PrescriptionIntegrityTest.php` then `000500_create_medication_tables.php`; prove signed/blocked checks and immutable prescription behavior; focused test and trigger-first down.
- [ ] 3.6 RED/GREEN/REFACTOR: laboratory tests then `000600_create_laboratory_tables.php`; split orders/specimens from result versions/alerts if needed, keeping tests with DDL; focused tests and version-preserving rollback.
- [ ] 3.7 RED/GREEN/REFACTOR: audit/messaging/immutability tests then `000700_create_audit_and_sync_tables.php` and `000800_create_immutable_record_triggers.php`; prove append-only, idempotency and finalized-row protection; focused tests, independently rollback triggers then tables.

## Phase 4: Models, Factories, Seeders (PRs 13–19)

- [ ] 4.1 RED/GREEN/REFACTOR: `CentralModelRelationsTest.php`; create `app/Models/Central/{CentralModel,...15 models}.php`; prove connection/key/casts/direct relations; focused test, `composer dump-autoload`; file-removal rollback.
- [ ] 4.2 RED/GREEN/REFACTOR: care model relation tests; create Hospital base plus care models under `app/Models/Hospital/`; prove metadata and scalar logical IDs; focused test and removable model boundary.
- [ ] 4.3 RED/GREEN/REFACTOR: clinical relation tests; add clinical Hospital models; prove relations/casts and no cross-DB ORM relation; focused test and removable boundary.
- [ ] 4.4 RED/GREEN/REFACTOR: laboratory/integration relation tests; add remaining Hospital models; prove relation graph; focused test and removable boundary.
- [ ] 4.5 RED/GREEN/REFACTOR: `FederatedFactoryGraphTest.php`; add CENTRAL factories; prove valid graph construction on CENTRAL; focused test and factory-only rollback.
- [ ] 4.6 RED/GREEN/REFACTOR: same factory test; add HOSPITAL factories; prove valid clinical/lab graphs; focused test and factory-only rollback.
- [ ] 4.7 RED/GREEN/REFACTOR: `DatabaseSeederTest.php`; add `CentralDatabaseSeeder.php`, `HospitalDatabaseSeeder.php`, `DatabaseSeeder.php`; prove deterministic idempotence and exactly four CENTRAL hospitals/one branch; focused test and fresh-without-seed rollback.

## Phase 5: Final Verification and Documentation (PR 20–21)

- [ ] 5.1 RED/GREEN/REFACTOR: add `SchemaInventoryTest.php`, `ForeignKeyIndexCoverageTest.php`, `FullMigrationRollbackTest.php`; prove exactly 15/27 entities, no cross-DB FK, indexed local FKs, clean failure recovery and reinstall; run full `database-integrity` group.
- [ ] 5.2 RED/GREEN/REFACTOR: create `docs/database/federated-postgresql-development.md`, link from `README.md`; document exact PostgreSQL-only install/reset/rollback/seed/test commands, ownership, blockers and no out-of-scope modules; run `composer validate --strict`, `vendor/bin/pint --test`, `composer test`.
