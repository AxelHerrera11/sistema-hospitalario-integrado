# Delta for Federated Database Topology

## ADDED Requirements

### Requirement: Destructive federated topology
The system SHALL replace the SQLite/unified snapshot with separate named `central` and `hospital` PostgreSQL connections and physically distinct databases. The rebuild MUST be clean and destructive: no backfill, compatibility schema, dual writes, or legacy preservation.

#### Scenario: Fresh installation
- GIVEN disposable CENTRAL and HOSPITAL PostgreSQL databases
- WHEN the ordered migration command runs
- THEN each database installs only its owned migration tree and reports the expected inventory
- AND no SQLite connection or table is used.

#### Scenario: Isolation
- GIVEN a table owned by one database
- WHEN catalog and migration assertions inspect both databases
- THEN the table is absent from the other database and no physical cross-database FK exists.

### Requirement: Ownership and connection pinning
CENTRAL SHALL own network, identity, MPI, consent/grants, network audit, and synchronization-control data. Each HOSPITAL SHALL own local identity references and clinical data. Persistence models and migrations MUST remain connection-pinned and MUST NOT encode cross-database ORM relations.

#### Scenario: Ownership violation
- GIVEN a proposed clinical table in CENTRAL or network-control table in HOSPITAL
- WHEN topology verification runs
- THEN verification fails with the owning database and expected location.

### Requirement: Rollback and operational prerequisites
The system SHALL document and test independent down/up rollback on disposable databases. Acceptance MUST require PostgreSQL 15+, `pdo_pgsql`, distinct test databases, and HOSPITAL `btree_gist`; unavailable prerequisites MUST block acceptance, never fall back to SQLite.

#### Scenario: Failed prerequisite
- GIVEN PostgreSQL, `btree_gist`, or a distinct test database is unavailable
- WHEN the integrity suite starts
- THEN it reports a diagnostic failure and does not claim acceptance.
