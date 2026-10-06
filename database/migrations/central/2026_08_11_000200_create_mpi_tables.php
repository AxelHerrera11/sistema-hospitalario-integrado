<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE mpi_patients (
                mpi_patient_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                normalized_name text NOT NULL,
                birth_date date,
                sex text CHECK (sex IN ('female', 'male', 'unknown', 'other')),
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'merged')),
                merged_into_id uuid REFERENCES mpi_patients(mpi_patient_id) ON DELETE RESTRICT,
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT mpi_patients_no_self_merge CHECK (merged_into_id IS NULL OR merged_into_id <> mpi_patient_id),
                CONSTRAINT mpi_patients_merge_status_validity CHECK ((status = 'merged') = (merged_into_id IS NOT NULL))
            );
            CREATE INDEX mpi_patients_merged_into_id_index ON mpi_patients (merged_into_id);

            CREATE TABLE mpi_identifiers (
                mpi_identifier_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                mpi_patient_id uuid NOT NULL REFERENCES mpi_patients(mpi_patient_id) ON DELETE RESTRICT,
                type text NOT NULL,
                issuer text NOT NULL,
                value_encrypted text NOT NULL,
                search_hash text NOT NULL,
                verified_at timestamptz,
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'revoked', 'historical')),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            );
            CREATE INDEX mpi_identifiers_mpi_patient_id_index ON mpi_identifiers (mpi_patient_id);
            CREATE UNIQUE INDEX mpi_identifiers_active_identity_unique ON mpi_identifiers (issuer, type, search_hash) WHERE status = 'active';

            CREATE TABLE patient_hospital_links (
                link_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                mpi_patient_id uuid NOT NULL REFERENCES mpi_patients(mpi_patient_id) ON DELETE RESTRICT,
                hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE RESTRICT,
                local_patient_global_id uuid NOT NULL,
                status text NOT NULL DEFAULT 'linked' CHECK (status IN ('pending', 'linked', 'unlinked')),
                linked_at timestamptz NOT NULL DEFAULT now(),
                unlinked_at timestamptz,
                confidence numeric NOT NULL CHECK (confidence >= 0 AND confidence <= 1),
                metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(metadata) = 'object'),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT patient_hospital_links_temporal_validity CHECK (unlinked_at IS NULL OR unlinked_at > linked_at),
                CONSTRAINT patient_hospital_links_unlinked_status_validity CHECK ((status = 'unlinked') = (unlinked_at IS NOT NULL)),
                CONSTRAINT patient_hospital_links_hospital_patient_unique UNIQUE (hospital_id, local_patient_global_id)
            );
            CREATE INDEX patient_hospital_links_mpi_patient_id_index ON patient_hospital_links (mpi_patient_id);
            CREATE INDEX patient_hospital_links_hospital_id_index ON patient_hospital_links (hospital_id);

            CREATE TABLE identity_match_candidates (
                candidate_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                mpi_patient_id uuid NOT NULL REFERENCES mpi_patients(mpi_patient_id) ON DELETE RESTRICT,
                link_id uuid NOT NULL REFERENCES patient_hospital_links(link_id) ON DELETE RESTRICT,
                score numeric NOT NULL CHECK (score >= 0 AND score <= 1),
                rules jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(rules) = 'object'),
                status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
                decision text,
                reviewed_by uuid REFERENCES central_users(user_id) ON DELETE RESTRICT,
                reviewed_at timestamptz,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT identity_match_candidates_decision_validity CHECK (
                    (status = 'pending' AND decision IS NULL AND reviewed_by IS NULL AND reviewed_at IS NULL)
                    OR (status IN ('approved', 'rejected') AND decision IS NOT NULL AND reviewed_by IS NOT NULL AND reviewed_at IS NOT NULL)
                )
            );
            CREATE INDEX identity_match_candidates_mpi_patient_id_index ON identity_match_candidates (mpi_patient_id);
            CREATE INDEX identity_match_candidates_link_id_index ON identity_match_candidates (link_id);
            CREATE INDEX identity_match_candidates_reviewed_by_index ON identity_match_candidates (reviewed_by);
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TABLE identity_match_candidates; DROP TABLE patient_hospital_links; DROP TABLE mpi_identifiers; DROP TABLE mpi_patients;');
    }
};
