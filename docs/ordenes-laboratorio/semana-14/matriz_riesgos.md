# Matriz de riesgos SQA

## Escalas

| Probabilidad | Valor | Impacto | Valor |
|---|---:|---|---:|
| Rara | 1 | Insignificante | 1 |
| Posible | 2 | Moderado | 2 |
| Probable | 3 | Grave | 3 |
| Casi segura | 4 | Crítico | 4 |

**Nivel:** Bajo 1–4 · Medio 5–8 · Alto 9–12 · Crítico 13–16.
Los riesgos de seguridad y trazabilidad tienen **tolerancia cero** aunque su producto sea Medio.

## Matriz

| ID | Riesgo | Causa | P | I | Nivel | Control preventivo | Control detectivo | Responsable | Contingencia |
|---|---|---|---:|---:|---|---|---|---|---|
| R1 | Escalada de privilegios | `X-Permissions` confiada al cliente o gateway mal configurado | 3 | 4 | Alto | Gateway sobrescribe la cabecera; permiso por operación; acceso directo bloqueado | Pruebas de 403; monitoreo de permisos; revisión de configuración | Seguridad / Infra | Bloquear API directa; revocar sesiones; auditar accesos |
| R2 | Orden duplicada por reintento | Cliente reintenta una escritura sin clave o con otra clave | 2 | 3 | Medio | `UNIQUE (tenant_id, idempotency_key)`; clave obligatoria; cliente no reintenta escrituras | Prueba de replay; búsqueda de órdenes con mismo paciente/pruebas en ventana corta | Autor | Cancelar duplicado con motivo; conservar ambos eventos |
| R3 | Orden parcial | Falla entre `INSERT` de orden, ítems o evento | 2 | 4 | Medio | Transacción única; rollback; restricciones NOT NULL/FK | Prueba de fallo de persistencia; consulta de órdenes sin ítems/eventos | Autor | Reparar desde historial; bloquear release |
| R4 | Transición inválida o cancelación de validada | Regla ausente o estado leído desactualizado | 2 | 4 | Medio | Máquina de estados en dominio; `ORDER_ALREADY_VALIDATED`; prueba de transiciones | Cobertura M3; monitoreo de 409; auditoría de secuencias | Autor | Corregir estado; abrir incidente clínico |
| R5 | Fuga entre hospitales | Consulta sin `tenant_id` o caché mal aislada | 2 | 4 | Medio (tolerancia cero) | Filtro obligatorio en repositorio; 404 entre tenants; sin caché compartida | Pruebas de aislamiento; monitoreo de 404 anómalos | Seguridad | Incidente; bloqueo; revisión de todas las consultas |
| R6 | Deriva contrato–implementación | OpenAPI no actualizado o respuesta no documentada | 3 | 3 | Alto | Contrato revisado en el mismo PR; checklist C-12; pruebas de contrato | Comparación de rutas/códigos; M4 | Autor / QA | Corregir contrato o código; bloquear integración |
| R7 | Flujo inusable en móvil o teclado | Layout que oculta acciones o foco atrapado | 2 | 3 | Medio | Diseño responsive; 44 px; foco visible; revisión de teclado | Capturas 390 px; prueba manual con teclado; M2 | Autor / QA | Corrección de UX; no publicar el flujo |
| R8 | Dependencia innecesaria | Framework o librería no justificada | 2 | 2 | Bajo | Revisión de `composer.json` e imports; PHP 8.2+ y PDO | Checklist C-13; análisis de dependencias | Autor | Retirar dependencia; volver a la versión simple |
| R9 | Historial alterado o incompleto | `UPDATE`/`DELETE` sobre eventos o campos faltantes | 1 | 4 | Medio (tolerancia cero) | Append-only; sin endpoints de edición; campos obligatorios; permisos de BD | Consulta de eventos incompletos; M6; revisión de logs | QA / DBA | Restaurar desde respaldo; auditar; corregir transición |

## Mapa 4×4

| Impacto \ Probabilidad | 1 Rara | 2 Posible | 3 Probable | 4 Casi segura |
|---|---|---|---|---|
| 4 Crítico | R9 | R3, R4, R5 | R1 | — |
| 3 Grave | — | R2, R7 | R6 | — |
| 2 Moderado | — | R8 | — | — |
| 1 Insignificante | — | — | — | — |

## Riesgos residuales aceptados

| Riesgo | Residual | Justificación | Aprobación |
|---|---|---|---|
| R1 | Medio | El gateway es el control principal; en el entorno académico no hay despliegue real | Pendiente |
| R5 | Bajo | Las pruebas de aislamiento existen; falta monitoreo real | Pendiente |
| R7 | Bajo | El prototipo se revisó a 390 px; falta auditoría formal | Pendiente |

Los riesgos residuales deben aprobarse en la revisión SQA del 2026-12-07. Ninguno se considera
cerrado por el hecho de estar documentado.
