<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<'SQL'
            CREATE TABLE patient_consents (
                consent_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                mpi_patient_id uuid NOT NULL REFERENCES mpi_patients(mpi_patient_id) ON DELETE RESTRICT,
                purpose text NOT NULL CHECK (btrim(purpose) <> ''),
                data_scope jsonb NOT NULL CHECK (jsonb_typeof(data_scope) = 'object'),
                allowed_hospitals jsonb NOT NULL CHECK (jsonb_typeof(allowed_hospitals) = 'array'),
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'revoked', 'expired')),
                valid_from timestamptz NOT NULL,
                valid_to timestamptz,
                captured_by uuid NOT NULL REFERENCES central_users(user_id) ON DELETE RESTRICT,
                evidence_ref text NOT NULL CHECK (btrim(evidence_ref) <> ''),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT patient_consents_valid_period CHECK (valid_to IS NULL OR valid_to > valid_from)
            );
            CREATE INDEX patient_consents_mpi_patient_id_index ON patient_consents (mpi_patient_id);
            CREATE INDEX patient_consents_captured_by_index ON patient_consents (captured_by);

            CREATE TABLE cross_hospital_access_grants (
                grant_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                mpi_patient_id uuid NOT NULL REFERENCES mpi_patients(mpi_patient_id) ON DELETE RESTRICT,
                requester_hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE RESTRICT,
                source_hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE RESTRICT,
                requester_user_id uuid NOT NULL REFERENCES central_users(user_id) ON DELETE RESTRICT,
                consent_id uuid REFERENCES patient_consents(consent_id) ON DELETE RESTRICT,
                grant_type text NOT NULL CHECK (grant_type IN ('normal', 'break_glass')),
                purpose text NOT NULL CHECK (btrim(purpose) <> ''),
                data_scope jsonb NOT NULL CHECK (jsonb_typeof(data_scope) = 'object'),
                status text NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'revoked', 'expired')),
                valid_from timestamptz NOT NULL,
                valid_to timestamptz NOT NULL,
                justification text,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT cross_hospital_access_grants_hospitals_differ CHECK (requester_hospital_id <> source_hospital_id),
                CONSTRAINT cross_hospital_access_grants_valid_period CHECK (valid_to > valid_from),
                CONSTRAINT cross_hospital_access_grants_type_validity CHECK (
                    (grant_type = 'normal' AND consent_id IS NOT NULL AND justification IS NULL)
                    OR (grant_type = 'break_glass' AND consent_id IS NULL AND justification IS NOT NULL AND btrim(justification) <> '' AND valid_to <= valid_from + interval '1 hour')
                )
            );
            CREATE INDEX cross_hospital_access_grants_mpi_patient_id_index ON cross_hospital_access_grants (mpi_patient_id);
            CREATE INDEX cross_hospital_access_grants_requester_hospital_id_index ON cross_hospital_access_grants (requester_hospital_id);
            CREATE INDEX cross_hospital_access_grants_source_hospital_id_index ON cross_hospital_access_grants (source_hospital_id);
            CREATE INDEX cross_hospital_access_grants_requester_user_id_index ON cross_hospital_access_grants (requester_user_id);
            CREATE INDEX cross_hospital_access_grants_consent_id_index ON cross_hospital_access_grants (consent_id);

            CREATE TABLE cross_hospital_access_audits (
                access_event_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                correlation_id uuid NOT NULL,
                grant_id uuid REFERENCES cross_hospital_access_grants(grant_id) ON DELETE RESTRICT,
                actor_user_id uuid NOT NULL REFERENCES central_users(user_id) ON DELETE RESTRICT,
                mpi_patient_id uuid NOT NULL REFERENCES mpi_patients(mpi_patient_id) ON DELETE RESTRICT,
                requester_hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE RESTRICT,
                source_hospital_id uuid NOT NULL REFERENCES hospitals(hospital_id) ON DELETE RESTRICT,
                action text NOT NULL CHECK (btrim(action) <> ''),
                resource_scope jsonb NOT NULL CHECK (jsonb_typeof(resource_scope) = 'object'),
                decision text NOT NULL CHECK (decision IN ('allowed', 'denied')),
                reason text,
                occurred_at timestamptz NOT NULL,
                network_metadata jsonb NOT NULL DEFAULT '{}'::jsonb CHECK (jsonb_typeof(network_metadata) = 'object')
            );
            CREATE INDEX cross_hospital_access_audits_grant_id_index ON cross_hospital_access_audits (grant_id);
            CREATE INDEX cross_hospital_access_audits_actor_user_id_index ON cross_hospital_access_audits (actor_user_id);
            CREATE INDEX cross_hospital_access_audits_mpi_patient_id_index ON cross_hospital_access_audits (mpi_patient_id);
            CREATE INDEX cross_hospital_access_audits_requester_hospital_id_index ON cross_hospital_access_audits (requester_hospital_id);
            CREATE INDEX cross_hospital_access_audits_source_hospital_id_index ON cross_hospital_access_audits (source_hospital_id);
            CREATE INDEX cross_hospital_access_audits_correlation_id_index ON cross_hospital_access_audits (correlation_id);

            CREATE TABLE break_glass_reviews (
                review_id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                grant_id uuid NOT NULL REFERENCES cross_hospital_access_grants(grant_id) ON DELETE RESTRICT,
                reviewer_id uuid REFERENCES central_users(user_id) ON DELETE RESTRICT,
                status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'justified', 'not_justified')),
                finding text,
                reviewed_at timestamptz,
                notification_status text NOT NULL DEFAULT 'pending' CHECK (notification_status IN ('pending', 'sent', 'failed')),
                supersedes_id uuid REFERENCES break_glass_reviews(review_id) ON DELETE RESTRICT,
                created_at timestamptz NOT NULL DEFAULT now(),
                CONSTRAINT break_glass_reviews_review_validity CHECK (
                    (status = 'pending' AND reviewer_id IS NULL AND finding IS NULL AND reviewed_at IS NULL)
                    OR (status IN ('justified', 'not_justified') AND reviewer_id IS NOT NULL AND finding IS NOT NULL AND btrim(finding) <> '' AND reviewed_at IS NOT NULL)
                )
            );
            CREATE INDEX break_glass_reviews_grant_id_index ON break_glass_reviews (grant_id);
            CREATE INDEX break_glass_reviews_reviewer_id_index ON break_glass_reviews (reviewer_id);
            CREATE UNIQUE INDEX break_glass_reviews_supersedes_id_unique ON break_glass_reviews (supersedes_id) WHERE supersedes_id IS NOT NULL;

            CREATE OR REPLACE FUNCTION enforce_normal_grant_consent() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE consent patient_consents%ROWTYPE;
            BEGIN
                IF NEW.grant_type <> 'normal' THEN RETURN NEW; END IF;
                SELECT * INTO consent FROM patient_consents WHERE consent_id = NEW.consent_id;
                IF NOT FOUND OR consent.status <> 'active' OR consent.valid_from > now()
                    OR (consent.valid_to IS NOT NULL AND consent.valid_to <= now())
                    OR consent.mpi_patient_id <> NEW.mpi_patient_id
                    OR consent.purpose <> NEW.purpose
                    OR NOT consent.allowed_hospitals @> jsonb_build_array(NEW.source_hospital_id::text)
                    OR NOT consent.data_scope @> NEW.data_scope THEN
                    RAISE EXCEPTION 'normal grants require a current matching and covering consent';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER cross_hospital_access_grants_consent_guard BEFORE INSERT OR UPDATE ON cross_hospital_access_grants FOR EACH ROW EXECUTE FUNCTION enforce_normal_grant_consent();

            CREATE OR REPLACE FUNCTION enforce_break_glass_review_insert() RETURNS trigger LANGUAGE plpgsql AS $$
            DECLARE current_review_exists boolean;
            BEGIN
                PERFORM 1 FROM cross_hospital_access_grants WHERE grant_id = NEW.grant_id AND grant_type = 'break_glass' FOR UPDATE;
                IF NOT FOUND THEN RAISE EXCEPTION 'break-glass reviews require a break_glass grant'; END IF;
                IF NEW.supersedes_id IS NULL THEN
                    SELECT EXISTS (SELECT 1 FROM break_glass_reviews parent WHERE parent.grant_id = NEW.grant_id AND NOT EXISTS (SELECT 1 FROM break_glass_reviews child WHERE child.supersedes_id = parent.review_id)) INTO current_review_exists;
                    IF current_review_exists THEN RAISE EXCEPTION 'only one current break-glass review is allowed'; END IF;
                ELSIF NOT EXISTS (SELECT 1 FROM break_glass_reviews parent WHERE parent.review_id = NEW.supersedes_id AND parent.grant_id = NEW.grant_id AND NOT EXISTS (SELECT 1 FROM break_glass_reviews child WHERE child.supersedes_id = parent.review_id)) THEN
                    RAISE EXCEPTION 'a correction must supersede the current review for the same grant';
                END IF;
                RETURN NEW;
            END;
            $$;
            CREATE OR REPLACE FUNCTION prevent_break_glass_review_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN RAISE EXCEPTION 'break_glass_reviews are immutable; append a correction instead'; END;
            $$;
            CREATE TRIGGER break_glass_reviews_insert_guard BEFORE INSERT ON break_glass_reviews FOR EACH ROW EXECUTE FUNCTION enforce_break_glass_review_insert();
            CREATE TRIGGER break_glass_reviews_immutable BEFORE UPDATE OR DELETE ON break_glass_reviews FOR EACH ROW EXECUTE FUNCTION prevent_break_glass_review_mutation();

            CREATE OR REPLACE FUNCTION prevent_cross_hospital_access_audit_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN RAISE EXCEPTION 'cross_hospital_access_audits is append-only'; END;
            $$;
            CREATE TRIGGER cross_hospital_access_audits_immutable BEFORE UPDATE OR DELETE ON cross_hospital_access_audits FOR EACH ROW EXECUTE FUNCTION prevent_cross_hospital_access_audit_mutation();
            SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared('DROP TRIGGER cross_hospital_access_audits_immutable ON cross_hospital_access_audits; DROP FUNCTION prevent_cross_hospital_access_audit_mutation(); DROP TRIGGER break_glass_reviews_immutable ON break_glass_reviews; DROP TRIGGER break_glass_reviews_insert_guard ON break_glass_reviews; DROP FUNCTION prevent_break_glass_review_mutation(); DROP FUNCTION enforce_break_glass_review_insert(); DROP TRIGGER cross_hospital_access_grants_consent_guard ON cross_hospital_access_grants; DROP FUNCTION enforce_normal_grant_consent(); DROP TABLE break_glass_reviews; DROP TABLE cross_hospital_access_audits; DROP TABLE cross_hospital_access_grants; DROP TABLE patient_consents;');
    }
};
