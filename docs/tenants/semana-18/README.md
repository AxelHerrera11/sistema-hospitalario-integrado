# Semana 18 — Evaluación final y defensa

**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación (ASII-25).

**Tarea:** demostrar el módulo funcionando, comprobar que todo está integrado y reunir la evidencia completa.

## Entregables

| Archivo | Contenido |
|---|---|
| [`guion-demo.md`](guion-demo.md) | Demo de 10 minutos, plan B y preguntas probables |
| [`evidencia-completa.md`](evidencia-completa.md) | Semanas 1–18 con PR, estado final de los 15 RF y hallazgos |
| [`evidencias/01-demo.txt`](evidencias/01-demo.txt) | Salida real de la demo sobre la versión integrada |
| [`evidencias/02-integracion.txt`](evidencias/02-integracion.txt) | Integración local de `develop` con los PR abiertos y verificación completa |

## PR integrado

101 PR de Cindy están fusionados en `develop` (entregas semanales #181–#195, cadena del micro-HIS y la parte 1 de la
Semana 16, #1489), además de la autenticación Laravel de Marielos (#501–#515). Los PR abiertos (partes 2–5 de la
Semana 16, #1503–#1506, y la Semana 17, #1510) se fusionaron localmente sobre `develop` junto con los de los otros
módulos (#1507, #1509) **sin conflictos**; sobre esa versión se ejecutaron las pruebas y la demo.

## Resultado sobre la versión integrada (2026-10-29)

| Verificación | Resultado |
|---|---|
| `tests/Feature/Tenants` y `tests/Unit/Tenants` | 107 pruebas, 540 aserciones OK |
| Demo HTTP contra el micro-HIS (MFA, refresh, bloqueo, roles, cabeceras) | Ver [`01-demo.txt`](evidencias/01-demo.txt) |
| Pipeline completo del grupo | Ver [`02-integracion.txt`](evidencias/02-integracion.txt) |

## Qué se defiende

1. **Identidad por hospital:** un usuario CENTRAL con roles y sesiones separados por hospital.
2. **Sesiones seguras:** JWT de 15 minutos, refresh rotativo con detección de reutilización y revocación inmediata.
3. **Cuentas privilegiadas:** MFA TOTP de un solo uso, bloqueo tras 5 fallos y sin escalada a `superadmin`.
4. **Honestidad de la evidencia:** se retiraron los resultados v1.0 que nunca se ejecutaron (E-TEN-16-01) y cada
   cifra de este módulo sale de una corrida guardada.

## Límites declarados

RF-12, RF-13 y RF-15 sin prueba o sin implementar; WebAuthn sin implementar; RNF de disponibilidad y carga sin
medir; validación contra CENTRAL en PostgreSQL pendiente; endurecimiento de Laravel pendiente de acuerdo.
