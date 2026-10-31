# Gobernanza y Auditoría — Semana 18: evaluación final y defensa

## Objetivo

Demostrar el módulo funcionando, comprobar que todo está integrado y reunir la evidencia completa para la defensa.

## Entregables

| Entregable | Ubicación |
|---|---|
| Demo de 10 minutos, plan B y preguntas probables | [guion-demo.md](guion-demo.md) |
| Semanas 1–18 con PR, estado final de RF/RNF y hallazgos | [evidencia-completa.md](evidencia-completa.md) |
| Salida real de la demo sobre la versión integrada | [evidencias/01-demo.txt](evidencias/01-demo.txt) |
| Integración local de `develop` con los PR abiertos y verificación completa | [evidencias/02-integracion.txt](evidencias/02-integracion.txt) |

## PR integrado

26 PR del módulo están fusionados en `develop` (#564–#654 y #1481 de la Semana 16). El de la Semana 17 (#1507) está
abierto; se fusionó localmente sobre `develop` junto con los demás PR abiertos de mis módulos (#1503–#1506, #1509,
#1510) **sin conflictos**, y sobre esa versión se ejecutaron las suites y la demo.

## Resultado sobre la versión integrada (2026-10-29)

| Verificación | Resultado |
|---|---|
| Suites del micro-HIS (9, incluida `seguridad`) | Todas aprobadas, ninguna omitida |
| Endpoint Laravel con JWT y RBAC reales (`GovernanceHttpTest`) | 5/5 |
| Demo HTTP: consulta, filtros, 403, aislamiento y 503 | Ver [01-demo.txt](evidencias/01-demo.txt) |
| Pipeline completo del grupo | Ver [02-integracion.txt](evidencias/02-integracion.txt) |

## Qué se defiende

1. **Bitácora confiable:** append-only, cadena de hashes compatible con Tenants y política de fallo por criticidad.
2. **Consulta segura:** identidad y tenant del servidor, RBAC antes que la validación, errores sin detalle técnico.
3. **Datos mínimos:** ni secretos, ni datos clínicos, ni (desde la Semana 17) datos personales en el contexto.
4. **Integración real:** el endpoint corre en Laravel con el RBAC del proyecto y registra sus propias consultas.

## Límites declarados

Trigger append-only en PostgreSQL y rendimiento con datos reales sin validar; `auditoria.ver` hoy solo lo tiene
`superadmin`; falta un límite de peticiones por usuario común a la API.
