# Semana 18 — Evaluación final y defensa

**Tarea:** demostrar el módulo funcionando, mostrar que todo está integrado y reunir la evidencia completa para la
defensa.

## Entregables

| Archivo | Contenido |
|---|---|
| [`guion-demo.md`](guion-demo.md) | Demo de 10 minutos paso a paso, plan B por API y preguntas probables |
| [`evidencia-completa.md`](evidencia-completa.md) | Semanas 1–18 con carpeta y PR, estado final de cada RF/RNF, defectos y pendientes |
| [`evidencias/01-demo.txt`](evidencias/01-demo.txt) | Salida real de la demo sobre la versión integrada |
| [`evidencias/02-integracion.txt`](evidencias/02-integracion.txt) | Integración local de `develop` con todos los PR abiertos y verificación completa |

## PR integrado

Los 28 PR del módulo (Semanas 1–17) están **fusionados en `develop`**, incluidos los de las Semanas 16 (#1480) y
17 (#1496). Para comprobar que la entrega final no rompe nada, se fusionaron localmente (sin push) sobre `develop`
los 7 PR que el resto de mis módulos tiene abiertos (#1503–#1507, #1509, #1510): **sin conflictos**.

## Resultado sobre la versión integrada (2026-10-29)

| Verificación | Resultado |
|---|---|
| Micro-HIS del expediente (unitarias, caja blanca y negra, PDO, seguridad, arquitectura) | 98 OK, 0 FALLO |
| Laravel: `tests/Feature/ExpedienteMedico` (con `GovernanceHttpTest`) | 10 pruebas, 62 aserciones OK |
| Demo por HTTP contra el servidor | Ver [`01-demo.txt`](evidencias/01-demo.txt) |
| Pipeline completo del grupo | Ver [`02-integracion.txt`](evidencias/02-integracion.txt) |

## Qué se defiende

1. **Valor:** un expediente longitudinal por paciente, consultado solo por quien atiende al paciente y con cada
   acceso en la bitácora de Gobernanza.
2. **Diseño:** micro-HIS en capas (dominio, aplicación, integración, persistencia, presentación) con Repository
   PDO/InMemory y adaptador Laravel delgado; ningún archivo supera 400 líneas.
3. **Calidad:** 98 pruebas propias más 5 Feature; matriz RF/RNF → prueba; 11 defectos y hallazgos reales, todos
   cerrados salvo un riesgo que depende del grupo (límite de peticiones).
4. **Seguridad:** 13 amenazas STRIDE con control y prueba; sin enumeración de pacientes; datos mínimos y `no-store`.
5. **Operación:** despliegue on-premise por releases con rollback ensayado.

## Límites declarados

PostgreSQL real, lector de pantalla, teléfono físico y GitHub Actions (facturación de la organización) no se
pudieron probar; cada semana lo indica. La apertura en Laravel y el acceso de enfermería dependen de RBAC.
