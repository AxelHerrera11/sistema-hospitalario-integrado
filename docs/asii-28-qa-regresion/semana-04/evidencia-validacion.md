# Evidencia nueva — 28/10/2026

Se ejecutaron git status --short --branch, git branch --show-current,
git log -1 --oneline y git fetch origin. Rama Semana 4 limpia al inicio y
base 3ea30faa igual a origin/develop (0/0). Se leyeron narrativa de Semana 1,
RF/RNF y LSP de Semana 2 y arquitectura/PlantUML de Semana 3 desde su rama.
GitHub confirmó #1367 abierto/no fusionado; no se incorporaron sus commits.
No había núcleo QA manual reutilizable; qa-calidad-versiones pertenece a CI.

## Pruebas reproducibles

`php micro-his/qa-regresion/tests/run.php` → **17 aprobadas, 0 fallidas**:

| Casos ejecutados | Cantidad | Resultado |
|---|---|---|
| Camino feliz, recuperación, segunda alta y conservación del primero | 1 por adaptador | 2 PASS |
| Propósito, pasos y esperado vacíos/espacios (RF-01) | 3 por adaptador | 6 PASS |
| Formato incorrecto/null | 1 por adaptador | 2 PASS |
| Identidad inexistente → null | 1 por adaptador | 2 PASS |
| Identidad duplicada rechazada, original intacto | 1 por adaptador | 2 PASS |
| Save fallido con doble y con PDO real sin tabla | 2 | 2 PASS |
| Consulta PDO sin tabla → StorageFailure con causa PDOException | 1 | 1 PASS |

Datos sintéticos; SQLite en memoria y esquema exclusivo de demo. No se usan
PostgreSQL, .env, migraciones, credenciales ni datos clínicos. La batería propia
no informa número de aserciones ni cobertura porcentual; no se inventan métricas.

Demostración PowerShell ejecutada:

```powershell
'{"purpose":"Revisar login","steps":"Abrir formulario","expected":"Campos visibles"}' | php micro-his/qa-regresion/demo.php
# status 201; data contiene los tres campos y una identidad de 32 caracteres.
'no es JSON' | php micro-his/qa-regresion/demo.php
# status 400; error INVALID_JSON.
```

La demo atraviesa controlador, caso de uso, dominio, PDO y vista; sus datos no
sobreviven al proceso. No es UI ni API Laravel, no acredita campañas/defectos.
Validación final: lint de los 11 archivos PHP sin errores; dependencias revisadas
sin ciclos, Domain/Application sin PDO y Presentation sin SQL. Enlaces locales y
git diff --check / git diff --cached --check: PASS. Solo archivos ASII-28 propios.
