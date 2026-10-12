# Evidencia verificable

## Checklist de revision

- [x] Los RF describen acciones observables del modulo.
- [x] Los RNF cubren seguridad, auditoria, consistencia y datos sensibles.
- [x] Cada criterio de aceptacion puede probarse con un escenario.
- [x] El principio ISP se aplica separando interfaces por consumidor.
- [x] El diseno antes/despues muestra responsabilidades y dependencias.

## Matriz RF/RNF hacia diseno

| Requerimiento | Contrato o componente que lo cubre |
|---|---|
| RF-IR-01 | `PendingResultReader` |
| RF-IR-02 | `ResultCapturePort` |
| RF-IR-03 | `ResultRuleValidator` |
| RF-IR-04 | `ResultCorrectionPort` |
| RF-IR-05 | `ResultCorrectionPort` + persistencia de versiones |
| RF-IR-06 | `ResultAuditPort` |
| RF-IR-07 | `ResultNotificationPort` |
| RNF-IR-01 | middleware de autorizacion + contratos de caso de uso |
| RNF-IR-02 | versionamiento y auditoria |
| RNF-IR-03 | unidad de trabajo/transaccion |
| RNF-IR-04 | eventos de auditoria minimizados |
| RNF-IR-05 | validadores de entrada y dominio |
| RNF-IR-06 | interfaces segregadas |

## Validaciones locales realizadas

```bash
git status --short --branch
```

Resultado esperado antes de commit: solo archivos bajo `docs/ingreso-resultados/semana-02/`.

## Evidencia para defensa

Pregunta esperada: "Que cambia al aplicar ISP?"

Respuesta corta: antes todos los clientes dependian de un servicio grande. Despues cada cliente depende de una interfaz pequena: la bandeja solo lee pendientes, captura solo registra resultados, correccion solo versiona cambios, y auditoria/notificacion quedan como puertos independientes.
