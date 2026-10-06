# Delta for Central Control Schema

## ADDED Requirements

### Requirement: Exact CENTRAL MVP inventory
CENTRAL SHALL contain exactly these 15 entities: `network`, `hospital`, `central_user`, `hospital_membership`, `mpi_patient`, `mpi_identifier`, `patient_hospital_link`, `identity_match_candidate`, `patient_consent`, `cross_hospital_grant`, `cross_access_audit`, `break_glass_review`, `sync_node`, `sync_checkpoint`, `central_inbox_message`. Every entity SHALL use UUID keys, local FKs only, indexed FK columns, `TIMESTAMPTZ` instants, and constrained evolvable status values.

#### Scenario: Inventory and references
- GIVEN a fresh CENTRAL database
- WHEN inventory and catalog assertions run
- THEN exactly 15 MVP entities exist, all listed names are present, and no FK targets HOSPITAL.

### Requirement: Identity, authorization, and retention integrity
The schema SHALL enforce local uniqueness and validity: network hospital codes, active MPI identifiers, hospital links, memberships, one sync node per hospital, and one checkpoint per node/channel. Consent/grants SHALL require scope and validity; normal grants require consent, while `break_glass` requires a short expiry, justification, and review. Identity, consent, grant, and audit records SHALL be retained/revoked or anonymized, not cascaded into clinical deletion.

#### Scenario: Invalid grant
- GIVEN an expired or consentless normal grant, or an unjustified emergency grant
- WHEN constraint verification attempts insertion
- THEN the operation is rejected.

### Requirement: Central synchronization idempotency
`central_inbox_message` SHALL accept at-least-once delivery idempotently using the `(source_node_id,event_id)` uniqueness invariant, preserve failures for retry, and contain only minimal payload rather than a clinical record.

#### Scenario: Duplicate delivery
- GIVEN a processed message identified by source node and event ID
- WHEN the same message is delivered again
- THEN no second logical message is created and the original processing state remains auditable.
