# Validación de integridad de Prescripciones — PR #1151

Ejecución del 27 de octubre de 2026 (America/Guatemala), sobre c6bdb9fca445097ca8d6bb1c346d6e66d0025417.
PHP 8.2.12, PHPUnit 11.5.55 y PostgreSQL 16.15 en Docker.

Se usaron bases aisladas his_prescriptions_s16_central_test (puerto 55431) e
his_prescriptions_s16_hospital_test (puerto 55433), con btree_gist en HOSPITAL.
El bootstrap local solo dirige el autoload y APP_BASE_PATH al worktree probado.

| Comprobación | Resultado | Duración |
|---|---|---|
| PrescriptionIntegrityTest | 13 pruebas, 172 aserciones: OK | 29.763 s |
| Grupo database-integrity | 149 pruebas, 1701 aserciones: OK | 4 min 45.315 s |
| MigrationLayoutTest | 3 pruebas, 18 aserciones: OK | 0.353 s |

La prueba específica está incluida también en el grupo: los resultados no se suman
como si fueran pruebas distintas. Los seis datasets nuevos comprueban cero y valores
negativos para dosis, frecuencia y duración tanto en draft como al firmar, con
SQLSTATE 23514, nombre del CHECK y conservación del estado draft tras el rechazo.
La prueba de rollback calcula los pasos mediante hospitalRollbackSteps('2026_08_11_000900').

Comandos ejecutados desde la raíz del worktree, con las dos variables de base anteriores:

```sh
php vendor/phpunit/phpunit/phpunit --bootstrap ../../.review-validation/bootstrap.php tests/Database/Hospital/PrescriptionIntegrityTest.php
php vendor/phpunit/phpunit/phpunit --bootstrap ../../.review-validation/bootstrap.php --group database-integrity
php vendor/phpunit/phpunit/phpunit --bootstrap ../../.review-validation/bootstrap.php tests/Database/MigrationLayoutTest.php
```

Los logs y JUnit completos se conservaron en informes/prescripciones-1151-* del
workspace de trabajo. También pasaron php -l, Pint --test y git diff --check.
Esta evidencia corresponde a ejecución local; no acredita CI remoto ni despliegue.
