<?php

// Gestión de ejecuciones automatizadas y calidad de versiones (ASII-25). Almacén propio en SQLite, fuera de las
// bases CENTRAL y HOSPITAL; ":memory:" sirve para pruebas.
return [
    'runs_sqlite' => env('QA_RUNS_SQLITE', storage_path('app/qa/qa_runs.sqlite')),
];
