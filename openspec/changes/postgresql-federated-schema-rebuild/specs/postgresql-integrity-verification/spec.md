# Delta for PostgreSQL Integrity Verification

## ADDED Requirements

### Requirement: PostgreSQL-only TDD acceptance
The integrity suite SHALL run against PostgreSQL 15+ disposable CENTRAL and HOSPITAL databases, with `pdo_pgsql`, `pgsql`, and HOSPITAL `btree_gist`. It SHALL prove migration inventory, isolation, local FK/index coverage, UUID/logical-reference rules, checks, triggers, exclusions, idempotency, immutability, retention, versioning, and rollback. SQLite tests, emulation, or silent skips SHALL NOT satisfy acceptance.

#### Scenario: SQLite-only harness
- GIVEN SQLite is configured or PostgreSQL prerequisites are absent
- WHEN the database-integrity group runs
- THEN acceptance fails diagnostically and no green result is reported.

### Requirement: Rebuild and rollback proof
The suite SHALL test clean install, destructive wipe, down/up rollback, reinstall, and failure recovery independently for both migration trees. Verification SHALL confirm no out-of-scope controllers, APIs, views, auth workflow, backfill, or compatibility persistence was introduced; offline authentication SHALL remain explicitly unresolved and out of scope.

#### Scenario: Rollback rehearsal
- GIVEN a disposable database with the complete MVP schema
- WHEN migrations roll back and reinstall in the documented order
- THEN the database returns to the empty state and reinstalls with the same exact 15/27 inventories.

### Requirement: Reviewable delivery boundaries
Implementation slices SHALL use force-chained, stacked-to-main work units of at most 400 authored changed lines, retaining tests and relevant documentation with the behavior they verify. Each slice SHALL declare dependencies and a rollback boundary.

#### Scenario: Oversized slice
- GIVEN a proposed slice exceeding 400 authored changed lines
- WHEN review preparation runs
- THEN the slice is split coherently or has an explicit approved exception; generated/vendor noise is excluded from the authored count.
