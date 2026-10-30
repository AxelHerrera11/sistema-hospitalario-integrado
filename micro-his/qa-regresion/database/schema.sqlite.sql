-- Solo demostración y pruebas aisladas, no migración del HIS.
CREATE TABLE qa_manual_cases (
    id TEXT PRIMARY KEY NOT NULL,
    purpose TEXT NOT NULL,
    steps TEXT NOT NULL,
    expected TEXT NOT NULL
);
