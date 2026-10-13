# Contrato consumido por el prototipo

## Endpoints

- `GET /api/v1/lab-results/pending`: permiso `lab-results.read`.
- `POST /api/v1/lab-results/{id}/corrections`: permiso `lab-results.correct`, motivo obligatorio.

Headers: `Authorization`, `X-Tenant-ID`, `X-Correlation-ID`, `Idempotency-Key` para mutaciones.

## Estados de cliente

`loading`, `ready`, `offline`, `conflict`, `forbidden` y `error`. El cliente no interpreta reglas clinicas; muestra el error de dominio que devuelve el servidor.

## Migracion razonada

1. Simular repositorio HTTP con dobles y validar el flujo.
2. Probar lectura real contra un entorno de desarrollo sin datos reales.
3. Validar autenticacion, tenant, expiracion de token y conflicto de version.
4. Solo despues evaluar empaquetado NativePHP y distribucion.

La evidencia no declara infraestructura de produccion ni ejecucion real hasta completar esas verificaciones.
