# Presentación — Primera evaluación parcial (8 diapositivas)

> Fuente editable en Markdown: una diapositiva por sección `##`. Se puede proyectar tal cual o pasar a cualquier herramienta de diapositivas.

## 1. Problema y objetivo

- 29 módulos encadenados se integran en `develop` en 6 días; hoy no existe un pipeline de pruebas (solo `audit-chain-integrity.yml` y `branch-protection.yml`).
- Objetivo: que cada PR tenga una **decisión reproducible** (aprobado / rechazado / bloqueado) con evidencia conservada.

## 2. Actores y casos de uso

- Líder QA, desarrollador/a, pipeline CI (`ci-bot`), observador; RBAC y Gobernanza como sistemas externos.
- CU-01 planificar · CU-02 ejecutar etapas y E2E · CU-03/04 registrar resultado con evidencia · CU-05 evaluar gate · CU-06 consultar.
- Diagrama: [semana-01/01-casos-de-uso.svg](../semana-01/01-casos-de-uso.svg).

## 3. RF/RNF clave

- RF-05: el gate devuelve un veredicto con hallazgos por regla.
- RNF-02: E2E solo en ambientes aislados con datos ficticios (`production` no existe en el dominio).
- RNF-03: resultados y decisiones de solo inserción, huella SHA-256.

## 4. SOLID: DIP

- La regla vive en `Domain/QualityGate` (sin dependencias); los detalles (PDO, JUnit, GitHub) son adaptadores.
- Evidencia: AR01–AR03 y el mismo caso de uso con InMemory, PDO y un doble que falla.

## 5. Capas y Repository

- `QaHttpHandler` → `QaController` → casos de uso → dominio ← `PdoTestRunRepository` / `InMemoryTestRunRepository`.
- El controlador no tiene SQL ni reglas (AR04–AR06).

## 6. Contrato API y CI

- `/api/v1/qa/runs` con 6 operaciones, errores `{code, trace_id}`, permisos `qa.*` **propuestos** a María.
- Workflow `qa-ci-e2e.yml`: static → tests → e2e (PostgreSQL efímero) → quality-gate.

## 7. Decisión → evidencia

- Ver [matriz-decision-evidencia.md](matriz-decision-evidencia.md): cada decisión enlaza la prueba o el archivo que la demuestra.

## 8. Cambio práctico defendido

- Pedido: «un log vacío no debe tumbar el gate de toda la versión».
- Resultado: la evidencia adicional inválida se descarta con nota; el JUnit sigue contando; prueba de regresión CI21. Ver [cambio-practico.md](cambio-practico.md).
