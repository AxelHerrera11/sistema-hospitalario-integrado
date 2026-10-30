# Vista arquitectónica del Micro-HIS «Expediente médico electrónico base»

## Decisiones

| Decisión | Motivo | Dónde se ve |
|---|---|---|
| PHP 8.2 vanilla, sin framework ni Composer (autocarga propia) | Lo exige la tarea; igual que los demás micro-HIS del repositorio | `bootstrap.php` |
| Cuatro capas + `Integration` | `Integration` separa los adaptadores a contratos de **otros módulos** de la persistencia propia | `src/` |
| PDO con sentencias preparadas | Seguridad (RNF-EXP-04) y portabilidad PostgreSQL/SQLite | `src/Persistence/Pdo*.php` |
| Reutilizar RBAC, Gobernanza y Pacientes por sus contratos | No duplicar el trabajo de compañeros (plan de integración) | `src/Integration/*.php` |
| Una base por hospital, elegida por tenant | Así es la base federada real (CENTRAL + HOSPITAL por hospital) | `Persistence/HospitalRegistry.php` |

## Vistas

- **Contexto (C4-1):** [`01-c4-contexto.svg`](01-c4-contexto.svg) — actores, módulos vecinos reales y la base.
- **Contenedores (C4-2):** [`02-c4-contenedores.svg`](02-c4-contenedores.svg) — interfaz estática, front controller,
  micro-HIS, base del hospital y bitácora, como se ejecutan hoy.
- **Componentes por capa (C4-3 / UML de paquetes):** [`03-componentes-capas.svg`](03-componentes-capas.svg).

## Pruebas exigidas por la tarea

| Exigencia | Prueba | Adaptador |
|---|---|---|
| Camino feliz | C7 (caso de uso), N-01 (HTTP) | InMemory y PDO/SQLite |
| Regla de dominio | U-02 (paciente fallecido no recibe expediente), U-06 (episodio de otro paciente) | sin adaptadores |
| Error de persistencia | C6b (doble InMemory caído), P-06 (tabla ausente en PDO), N-11 (503 por HTTP) | doble y base de prueba |

Salida real: [`evidencias/pruebas-semana-03.txt`](evidencias/pruebas-semana-03.txt).
