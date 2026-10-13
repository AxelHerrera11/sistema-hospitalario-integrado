# Evidencia verificable

## Checklist de revision

- [x] Los RF describen acciones observables del modulo.
- [x] Los RNF cubren seguridad, auditoria, consistencia, concurrencia y datos sensibles.
- [x] Cada criterio de aceptacion puede probarse con un escenario.
- [x] El principio DIP se aplica invirtiendo dependencias hacia contratos.
- [x] El diseno antes/despues muestra responsabilidades y dependencias.

## Matriz RF/RNF hacia diseno

| Requerimiento | Contrato o componente que lo cubre |
| --- | --- |
| RF-VR-01 | `PendingResultRepository.findPending` |
| RF-VR-02 | `PendingResultRepository.findPending` + DTO de detalle |
| RF-VR-03 | `DecideLabResultReview.validate` + `ReviewPolicy` |
| RF-VR-04 | `DecideLabResultReview.reject` + validacion de motivo |
| RF-VR-05 | `ReviewPolicy` + version/estado en repositorio |
| RF-VR-06 | `ReviewAuditPort` |
| RF-VR-07 | `ReviewNotificationPort` |
| RNF-VR-01 | middleware de autorizacion + `ReviewPolicy` |
| RNF-VR-02 | `ReviewAuditPort` + version de resultado |
| RNF-VR-03 | unidad de trabajo/transaccion del caso de uso |
| RNF-VR-04 | control de version/estado en repositorio |
| RNF-VR-05 | eventos minimizados |
| RNF-VR-06 | puertos e interfaces del dominio |

## Validaciones locales realizadas

```bash
git status --short --branch
```

Resultado esperado antes de commit: solo archivos bajo `docs/validacion-resultados/semana-02/`.

## Evidencia para defensa

Pregunta esperada: "Que cambia al aplicar DIP?"

Respuesta corta: antes el servicio de validacion dependia directamente de PDO, correo y auditoria concreta. Despues el caso de uso depende de contratos: repositorio, politica, auditoria y notificacion. Los detalles concretos se conectan desde infraestructura y pueden reemplazarse sin cambiar la regla clinica.
