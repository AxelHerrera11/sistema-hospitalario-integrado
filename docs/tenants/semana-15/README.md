# Semana 15 — Pruebas unitarias, de caja blanca y de caja negra

**Sistema probado:** micro-HIS de Tenants en PHP vanilla (Semana 3, `app/Modules/Tenants`).
**Corrida:** 2026-10-21 · PHP 8.2.12 · PHPUnit 11.5.55 · Windows 11.

> **Estado: pruebas funcionales del micro-HIS ejecutadas y aprobadas. No es una aprobación para
> producción.** La versión anterior de este índice declaraba «APROBADO PARA PRODUCCIÓN», 90,67 % de
> cobertura y resultados de E2E, k6, caos, pentesting y recuperación ante desastres que nunca se
> ejecutaron. Esas cifras se retiraron.

## Entregables vigentes

| Archivo | Tipo | Contenido |
|---|---|---|
| `pruebas-unitarias-caja-blanca-negra.md` | Markdown | Resultado de la corrida, matriz RF → prueba, casos unitarios y de caja blanca |
| `pruebas-unitarias-caja-blanca-negra1.md` | Markdown | Casos de caja negra, cobertura por componente, comandos y criterios |
| `unit-tests.results.xml` | JUnit (PHPUnit) | 29 pruebas, 71 aserciones, 0 errores, 0 fallos |
| `whitebox-tests.results.xml` | JUnit (PHPUnit) | 17 pruebas, 42 aserciones, 0 errores, 0 fallos |
| `blackbox-tests.results.xml` | JUnit (PHPUnit) | 27 pruebas, 88 aserciones, 0 errores, 0 fallos |
| `feature-tests.results.xml` | JUnit (PHPUnit) | 33 pruebas, 139 aserciones, 0 errores, 0 fallos |
| `performance-report.html` | HTML | Micro-benchmark en proceso (no es prueba de carga) |
| `bench/micro-his-bench.php` | PHP | Script reproducible del micro-benchmark |

Los XML se generan con `phpunit --log-junit`; cada uno lleva en su cabecera el comando y el entorno.

### Archivos anteriores sin respaldo

`test-summary.md`, `matriz-rf-rnf-tests.md`, `accessibility-report.html`, `coverage/` y
`test-results/` son de la versión 1.0. Contienen cifras (cobertura, axe, E2E, k6) que no provienen
de ninguna corrida y XML escritos a mano. **No son evidencia**: prevalecen los archivos de la tabla
anterior. Quedan como antecedente hasta retirarlos en una entrega aparte, para no superar el límite
de 400 líneas por PR.

## Resumen de la corrida

| Categoría | Ejecutadas | Aprobadas | Fallidas | Evidencia |
|---|---|---|---|---|
| Unitarias | 29 | 29 | 0 | `unit-tests.results.xml` |
| Caja blanca (incluidas en unitarias) | 17 | 17 | 0 | `whitebox-tests.results.xml` |
| Caja negra (HTTP en proceso) | 27 | 27 | 0 | `blackbox-tests.results.xml` |
| *Feature* del micro-HIS (caja negra + smoke de Semana 3) | 33 | 33 | 0 | `feature-tests.results.xml` |
| Cobertura de líneas | — | — | — | **Sin medir** (sin Xdebug ni PCOV) |
| E2E, accesibilidad automatizada, carga, caos, pentesting | — | — | — | **Sin ejecutar** |

Requisitos funcionales con al menos una prueba aprobada: **12 de 15** (sin prueba: RF-12 sesión única,
RF-13 huella de dispositivo y RF-15 JIT por SSO; RF-05 solo en su parte TOTP).

## Rendimiento medido (en proceso, 200 iteraciones)

| Operación | p50 | p95 | p99 |
|---|---|---|---|
| `POST /auth/login` (Argon2id 64 MiB) | 170,41 ms | 178,54 ms | 192,84 ms |
| `GET /auth/me` | 0,14 ms | 0,24 ms | 0,33 ms |
| `POST /auth/refresh` | 0,11 ms | 0,19 ms | 0,36 ms |
| `POST /auth/logout` | 0,15 ms | 0,23 ms | 0,48 ms |

Sin red, servidor web ni base de datos. Los RNF de throughput, disponibilidad, RPO y RTO siguen **sin medir**.

## Cómo reproducir

```bash
vendor/bin/phpunit --group unitarias
vendor/bin/phpunit --group caja-blanca
vendor/bin/phpunit --group caja-negra
vendor/bin/phpunit tests/Feature/Tenants/MicroHisSmokeTest.php
php docs/tenants/semana-15/bench/micro-his-bench.php 200
```

Dependencia de integración: estas pruebas ejercitan el micro-HIS de la cadena de Semana 3
(#284 a #303), así que se integran después de ella.

## Pendiente

1. Medir cobertura con PCOV o Xdebug y publicar el reporte generado.
2. Pruebas para RF-12 y RF-13; RF-15 requiere implementar antes el aprovisionamiento JIT.
3. Prueba de carga con k6 sobre `app/Modules/Tenants/micro-his.php` servido por PHP-FPM.
4. Retirar los archivos v1.0 sin respaldo que se listan arriba.
