# Evidencia verificable

## Checklist de revision

- [x] Los RF describen un prototipo acotado y evaluable.
- [x] Los RNF evitan credenciales reales y datos clinicos reales.
- [x] Cada criterio de aceptacion puede probarse con datos semilla.
- [x] El principio ISP separa contratos por consumidor.
- [x] El diseno antes/despues muestra responsabilidades y dependencias.

## Matriz RF/RNF hacia diseno

| Requerimiento | Contrato o componente que lo cubre |
| --- | --- |
| RF-NP-01 | `SessionPort` |
| RF-NP-02 | `PendingItemReader` |
| RF-NP-03 | `LocalActionQueue.enqueue` |
| RF-NP-04 | `SyncGateway` + `LocalActionQueue` |
| RF-NP-05 | `ConnectivityProbe` |
| RF-NP-06 | `PrototypeTelemetry` |
| RNF-NP-01 | `SessionPort` + politica de no guardar secretos reales |
| RNF-NP-02 | `SyncGateway` simulado |
| RNF-NP-03 | matriz de hallazgos del prototipo |
| RNF-NP-04 | `PrototypeTelemetry` |
| RNF-NP-05 | interfaces segregadas |
| RNF-NP-06 | datos semilla ficticios |

## Validaciones locales realizadas

```bash
git status --short --branch
```

Resultado esperado antes de commit: solo archivos bajo `docs/nativephp/semana-02/`.

## Evidencia para defensa

Pregunta esperada: "Por que ISP ayuda en un prototipo NativePHP?"

Respuesta corta: porque permite evaluar cada parte del prototipo sin convertirlo en una aplicacion grande e inseparable. La UI depende de lectura y conectividad; sincronizacion depende de cola y gateway; telemetria queda separada. Si NativePHP no resulta viable en una pieza, la evidencia muestra exactamente donde esta el limite.
