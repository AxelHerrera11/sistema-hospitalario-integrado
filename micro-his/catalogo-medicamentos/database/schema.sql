-- Esquema local del micro-HIS (SQLite). NO es el slice H5 de la base HOSPITAL, que sigue pendiente.
-- Una base representa un solo hospital; por eso no hay columna de hospital.
CREATE TABLE IF NOT EXISTS medications (
    medication_id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    active_ingredient TEXT NOT NULL,
    pharmacological_group TEXT NOT NULL,
    validity TEXT NOT NULL DEFAULT 'active' CHECK (validity IN ('active', 'inactive'))
);
