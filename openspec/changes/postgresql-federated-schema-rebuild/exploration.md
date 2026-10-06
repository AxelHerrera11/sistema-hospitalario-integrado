## Exploration: PostgreSQL federated schema rebuild

### Current State
The Laravel application is still a single-database SQLite-oriented snapshot. The root migration tree contains tenant, users, permissions, admission, EMR, laboratory, audit/notification, cache, and jobs migrations. The application has 16 legacy integer/tenant-oriented Eloquent models, matching factories and seeders, and only example PHPUnit tests. `config/database.php` and `.env.example` retain SQLite as the default; `phpunit.xml` forces SQLite in-memory testing. The repository has no Composer `vendor/` directory, so the configured `composer test` command cannot yet run.

The approved target is a clean replacement, not a compatibility migration: two named PostgreSQL connections (`central` and `hospital`), physically distinct databases, separate central/hospital/framework migration trees, UUID domain keys, local foreign keys only, PostgreSQL-specific checks/indexes/exclusion constraints/triggers, minimal connection-pinned models, valid factories/seeders, and PostgreSQL integrity tests. The architecture document defines 15 CENTRAL and 27 HOSPITAL MVP entities; the implementation plan gives the 16 logical batches and expected 21 review slices.

Runtime preflight is incomplete: PHP exposes `pdo_pgsql` and `pgsql`, but `psql` is unavailable and no PostgreSQL service/database or `btree_gist` capability was verified. The plan correctly requires blocking acceptance rather than falling back to SQLite.

### Affected Areas
- `database/migrations/*.php` — incompatible unified/tenant domain migrations must be removed; cache/jobs are framework infrastructure to relocate.
- `config/database.php`, `.env.example`, `phpunit.xml`, `composer.json` — named connections, environment placeholders, PostgreSQL test databases, migration/test scripts, and the authoritative `composer test` harness.
- `app/Models/*.php` — legacy tenant/integer models conflict with UUID and connection ownership; replace with central/hospital base models and minimal relations.
- `database/factories/*.php`, `database/seeders/*.php` — current graphs target deleted tables; replace with connection-specific valid deterministic graphs.
- `tests/TestCase.php`, `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` — current harness cannot prove PostgreSQL behavior; add isolated dual-connection integrity harness and delete examples.
- `docs/database/federated-postgresql-development.md`, `README.md` — document database ownership, exact install/reset/rollback/seed/test commands and known non-DB boundaries.
- `docs/arquitectura/diagnostico-y-diseno-er-red-hospitalaria.md` — approved ownership, entity dictionary, logical-reference rule, and integrity rationale; reference only, not an implementation artifact.
- `docs/plans/2026-08-11-postgresql-federated-schema-rebuild.md` — approved execution blueprint, TDD batches, boundaries, and review forecast; not current state.

### Migration Replacement Boundaries
1. **Batch 1 foundation**: prove named connection isolation and migration layout first; relocate cache/jobs only, remove incompatible domain/auth persistence, and record expected feature-layer compile failures without touching controllers/routes/views.
2. **CENTRAL slices**: network/identity; MPI/linking; consent/grants/audit; sync/inbox. CENTRAL must never contain local clinical tables.
3. **HOSPITAL slices**: extension/professional identity; patient/care; scheduling/beds; clinical records/observations; medication; laboratory; audit/outbox/inbox/triggers. Cross-database IDs remain scalar logical references with no physical FK.
4. **Models/factories/seeders/docs** follow stabilized schema and relations, not precede it. Minimal models must not encode authorization workflows, cross-database Eloquent relations, or feature transitions.

### PostgreSQL Connection and Test Prerequisites
- Install Composer dependencies before PHPUnit/Pint; `composer test` is the configured command and currently cannot execute because `vendor/autoload.php` is absent.
- Provision two distinct PostgreSQL 15+ databases and configure credentials through environment variables; test names must differ from development names.
- Verify `pdo_pgsql`, `pgsql`, PostgreSQL server version, and `btree_gist` installation/creation in HOSPITAL. Current PHP extensions pass, but the client/service are unverified.
- Replace SQLite-only test assumptions with a PostgreSQL-only `database-integrity` group, explicit migration paths, dual-connection helpers, catalog assertions, and fresh disposable DBs for migration/rollback tests. Skips are diagnostic only, never acceptance.
- Validate Laravel's exact `migrate:fresh --path` behavior rather than assuming it combines migration trees; document an explicit `db:wipe` plus ordered migration fallback if required.

### Stacked-to-Main Slicing (400-line budget)
Use the mandated force-chained, stacked-to-main strategy. Each child targets main, remains independently reviewable, includes its tests/docs, and declares dependencies and rollback boundary. Preserve the plan's slices, splitting only where needed:

1. Foundation/config/harness (target ≤400; split auth cleanup if necessary).
2. CENTRAL network + identity/membership.
3. CENTRAL MPI/linking.
4. CENTRAL consent/grants/audit (likely >400; split grants only if rollback remains coherent or record a size exception).
5. CENTRAL sync/inbox.
6. HOSPITAL extension + professional identity.
7. HOSPITAL patient/care.
8. HOSPITAL scheduling/beds (keep exclusion tests with DDL).
9A/9B. HOSPITAL records/notes and observations if needed.
10. Medication/prescription.
11A/11B. Laboratory orders/specimens and result versions/alerts.
12A/12B. Hospital audit/integration and immutable triggers if needed.
13A/13B. CENTRAL models by identity/MPI and access/sync.
14A/14B/14C. HOSPITAL models by care, clinical, and lab/integration.
15A/15B. CENTRAL and HOSPITAL factories/seeders.
16. Full inventory, FK-index coverage, rollback rehearsal, and developer docs.

Tests MUST stay with the constraints or behavior they verify. Generated/vendor noise is excluded from authored line forecasts, but every changed file remains part of review identity.

### Approaches
1. **Clean federated rebuild with PostgreSQL-first TDD** — replace the incompatible persistence snapshot in dependency order.
   - Pros: matches approved architecture; proves PostgreSQL-only guarantees; clear ownership and rollback boundaries.
   - Cons: large change; feature-layer references to deleted models may fail; requires two databases and `btree_gist`.
   - Effort: High

2. **Incremental compatibility conversion of the current SQLite/tenant schema** — retain old tables and gradually convert types/ownership.
   - Pros: potentially fewer immediate compile failures.
   - Cons: contradicts the approved clean-rebuild/no-backfill decision; preserves ambiguous tenant boundaries and unsupported SQLite semantics; makes dual migration history and rollback harder.
   - Effort: High

### Recommendation
Proceed with the clean federated rebuild, beginning with the connection/test harness RED tests. It is the only approach consistent with the approved architecture and implementation plan. Treat PostgreSQL availability as an explicit prerequisite and keep feature/API breakage visible rather than expanding this persistence change into controllers, routes, views, or authentication design.

### Risks
- PostgreSQL service, `psql`, database creation privileges, or `btree_gist` may be unavailable; acceptance must remain blocked rather than silently using SQLite.
- Existing auth/routes/controllers may reference deleted `User`, tenant, and permission persistence; report these as out-of-scope blockers.
- Laravel migration path and refresh behavior across named connections may not match the plan and must be experimentally verified after dependencies exist.
- PostgreSQL exclusion constraints, JSONB checks, immutable triggers, and FK-index coverage cannot be validated with SQLite.
- Cross-row authorization and same-patient consistency are not safely expressible as ordinary checks; do not fake them in migrations.
- The total rebuild is forecast at 6,250–7,770 authored lines; polluted diffs or oversized slices threaten reviewability.
- The architecture still leaves offline authentication unresolved; `local_user_refs` must not be presented as credentials or complete offline auth.

### Ready for Proposal
Yes. The proposal should state that this is a destructive, clean persistence replacement with no backfill or compatibility layer; require PostgreSQL 15+, `pdo_pgsql`, distinct databases, and `btree_gist`; preserve the strict database-only scope; and adopt the force-chained stacked-to-main slices with a 400-line review budget.
