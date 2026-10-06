# Proposal: PostgreSQL Federated Schema Rebuild

## Intent

Replace the unified SQLite/tenant snapshot destructively with the approved PostgreSQL 15+ federated foundation. CENTRAL owns network data; each independent HOSPITAL owns clinical data.

## Scope

### In Scope
- Rebuild 15 CENTRAL and 27 HOSPITAL entities with separate migrations, UUID keys, local constraints, and logical cross-database references.
- Connection-pinned models, factories/seeders, framework-table relocation, and database documentation.
- PostgreSQL RED-GREEN-REFACTOR tests for constraints, indexes, exclusions, triggers, isolation, migrations, and rollback.
- Force-chained, stacked-to-main work units of at most 400 authored changed lines, retaining tests and relevant docs per slice.

### Out of Scope
- Data backfill, compatibility schema, dual writes, or legacy preservation.
- Controllers, APIs, views/UI, or module/business logic.
- Offline authentication remains unresolved; `local_user_refs` must not imply it.

## Capabilities

### New Capabilities
- `federated-database-topology`: Named CENTRAL/HOSPITAL connections, migrations, ownership, and isolation.
- `central-control-schema`: CENTRAL identity, MPI, consent/grants, audit, and synchronization.
- `hospital-clinical-schema`: HOSPITAL identity, care, clinical, laboratory, audit, and integration.
- `postgresql-integrity-verification`: PostgreSQL-only TDD, inventory, rollback, and constraint verification.

### Modified Capabilities
None; OpenSpec defines no existing capabilities.

## Approach

Establish dual-connection isolation and tests; build both migration trees; then add models, factories/seeders, and documentation. FKs remain within one database; cross-database UUIDs are scalar logical references. HOSPITAL requires `btree_gist` for GiST exclusions.

## Affected Areas

| Area | Impact | Description |
|---|---|---|
| `database/`, `app/Models/` | Replaced | Federated schema and persistence mapping |
| `config/database.php`, `.env.example`, `phpunit.xml`, `composer.json` | Modified | Connections and PostgreSQL harness |
| `tests/Database/`, `docs/database/`, `README.md` | New/Modified | Integrity proof and developer handoff |
| Feature/auth modules | Contract only | Legacy references may break; no fixes |

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Legacy consumers break | High | Surface blockers; preserve database-only scope |
| PostgreSQL or `btree_gist` unavailable | Medium | Block acceptance; never fall back to SQLite |
| Oversized review slices | High | Split coherent work units; require explicit exception otherwise |

## Rollback Plan

Revert slices independently and rehearse down/up on disposable databases. Restore the snapshot only by reverting the foundation slice; never use shared databases.

## Dependencies

- Apply owns runtime prerequisites: Composer dependencies, `pdo_pgsql`, distinct PostgreSQL 15+ development/test databases, privileges, and HOSPITAL `btree_gist`.

## Success Criteria

- [ ] Exactly 15 CENTRAL and 27 HOSPITAL entities install, isolate, rollback, and reinstall on distinct databases.
- [ ] PostgreSQL tests prove local FKs/indexes and required checks, exclusions, idempotency, and immutability; no physical cross-database FK exists.
- [ ] No out-of-scope application logic or data backfill is introduced.
