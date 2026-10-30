# Evidencia completa y PR integrados

Índice final del módulo: entregables por semana, PR, estado de cada requisito y hallazgos. Estados de los PR
consultados en GitHub el 2026-10-29. La tabla incluye las entregas semanales (`…-tenants-cindyruano`) y la cadena
del micro-HIS (`feature/asii-25-semana-NN[-parteP]`). La autenticación Laravel la integró Marielos (Mar-03) en
#501–#515 (Semanas 16–17 de su plan); no se duplica aquí.

## Semanas, carpetas y PR de Cindy

| Semana | Tema | Carpeta | PR integrados | PR abiertos |
|---|---|---|---|---|
| 01 | Análisis y UML | [`semana-01`](../semana-01/) | #181, #279–#281 | — |
| 02 | Requisitos y SOLID | [`semana-02`](../semana-02/) | #182, #282 | — |
| 03 | Arquitectura | [`semana-03`](../semana-03/) | #183, #283–#290, #294–#295, #297–#299, #301–#303 | — |
| 04 | MVC y Repository | [`semana-04`](../semana-04/) | #184, #304 | — |
| 05 | Cliente-servidor y API | [`semana-05`](../semana-05/) | #185, #305–#309 | — |
| 06 | 1.ª evaluación | [`semana-06`](../semana-06/) | #186, #310–#311 | — |
| 07 | Componentes y refactorización | [`semana-07`](../semana-07/) | #187, #312–#313 | — |
| 08 | UX y wireframes | [`semana-08`](../semana-08/) | #188, #314–#321 | — |
| 09 | Usabilidad y accesibilidad | [`semana-09`](../semana-09/) | #189, #322–#323 | — |
| 10 | Movilidad | [`semana-10`](../semana-10/) | #190, #358–#359, #364–#365, #368, #377, #383–#386, #391–#399 | — |
| 11 | Prototipo navegable | [`semana-11`](../semana-11/) | #191, #324–#333 | — |
| 12 | 2.ª evaluación | [`semana-12`](../semana-12/) | #192, #334 | — |
| 13 | Revisión técnica | [`semana-13`](../semana-13/) | #193, #335 | — |
| 14 | Plan SQA | [`semana-14`](../semana-14/) | #194, #336 | — |
| 15 | Pruebas unitarias, caja blanca y negra | [`semana-15`](../semana-15/) | #195, #338–#341, #344–#346, #348, #350–#354 | — |
| 16 | Integración y primera entrega | [`semana-16`](../semana-16/) | #1489 | #1503–#1506 |
| 17 | Seguridad y despliegue final | [`semana-17`](../semana-17/) | — | #1510 |
| 18 | Evaluación final y defensa | [`semana-18`](../semana-18/) | esta entrega | — |
| | | **Total** | **101** | **5** |

## Requisitos: estado final

Base: matriz de la Semana 15 (`../semana-15/pruebas-unitarias-caja-blanca-negra.md`), revalidada en las Semanas 16–17.

| Requisito | Estado | Evidencia final |
|---|---|---|
| RF-01 Alta de usuario por hospital | Aprobado | `RegisterUserFeatureTest`; smoke V-03 |
| RF-02 Política de contraseñas | Aprobado | `PasswordPolicyTest`; smoke (422) |
| RF-03 Roles por hospital | Aprobado | `MultiTenantIsolationFeatureTest`; «Admin hospital cannot grant superadmin» |
| RF-04 Autenticación con credenciales | Aprobado | `LoginMfaFeatureTest`; demo |
| RF-05 MFA | TOTP aprobado; WebAuthn **sin implementar** | `LoginMfaFeatureTest`; demo |
| RF-06 JWT de acceso | Aprobado | `TokenFactoryTest`; rotación del secreto en el despliegue |
| RF-07 Rotación de refresh y reutilización | Aprobado | `RefreshTokenRotatorTest`; demo |
| RF-08 Bloqueo de access tokens revocados | Aprobado | `RevokeSessionFeatureTest` |
| RF-09 Revocación (logout y forzada) | Aprobado | `RevokeSessionFeatureTest` |
| RF-10 Aislamiento entre hospitales | Aprobado | `MultiTenantIsolationFeatureTest` |
| RF-11 Bloqueo progresivo | Aprobado | `LoginMfaFeatureTest`; demo (423) |
| RF-12 Sesión única | **Sin prueba** | — |
| RF-13 Huella de dispositivo | **Sin prueba** | — |
| RF-14 Auditoría encadenada | Aprobado | `SecurityPrimitivesTest`; `hash_compat` de Gobernanza |
| RF-15 Aprovisionamiento JIT por SSO | **Sin implementar** | — |

Resultado: 12 de 15 RF con prueba aprobada. Los RNF de disponibilidad, throughput, RPO y RTO siguen **sin medir**
(no hay servidor del grupo); la latencia se midió solo en proceso (Semana 15).

## Hallazgos y correcciones

| Id | Semana | Estado |
|---|---|---|
| E-TEN-16-01 (archivos v1.0 con resultados nunca ejecutados) | 16 | Corregido en las partes 2–5 (#1503–#1506) |
| H-TEN-16-01 (respuestas con tokens sin `no-store`) | 16 → 17 | Corregido en el micro-HIS (#1510) |
| H-TEN-16-02 (zona horaria de `php.ini`) | 16 → 17 | Regla de despliegue: `date.timezone=UTC` |
| T-11 (`no-store` en Laravel) y T-13 (500 con CENTRAL caída) | 17 | Recomendaciones para Marielos |
| Endurecimiento de Laravel (Argon2id, bloqueo en caché) | 18 | Pendiente de acuerdo con Javier y Marielos |
