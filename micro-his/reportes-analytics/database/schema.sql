-- Esquema SQLite del episodio correspondiente al subset que lee el indicador
-- IND-RA-01 (alineado con care_episodes de la migracion HOSPITAL 000200):
-- tipos, estados y regla ended_at > started_at para filas finalizadas.
CREATE TABLE care_episodes (
    care_episode_id TEXT PRIMARY KEY,
    type TEXT NOT NULL CHECK (type IN ('outpatient', 'emergency', 'inpatient')),
    status TEXT NOT NULL CHECK (status IN ('open', 'closed', 'cancelled')),
    started_at TEXT NOT NULL,
    ended_at TEXT,
    service_code TEXT,
    CONSTRAINT care_episodes_status_period_check CHECK (
        (status = 'open' AND ended_at IS NULL)
        OR (status IN ('closed', 'cancelled') AND ended_at > started_at)
    )
);
