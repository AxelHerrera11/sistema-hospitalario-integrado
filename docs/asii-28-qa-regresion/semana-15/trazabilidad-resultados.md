# Trazabilidad, métricas y pendientes

Fuente: [ocho RF/cinco RNF reales](../semana-02/rf-rnf-criterios.md).
La [matriz de casos](./matriz-casos-prueba.md) relaciona los nueve IDs, entradas,
componentes, esperado/observado y estado; la [evidencia](./ejecucion-evidencias.md)
identifica cada PASS. Técnicos auxiliares no reciben un requisito inventado.

| Casos | Vínculo directo de aceptación / estado |
|---|---|
| UT-01, UT-02, UT-03 | RF-01: definición completa/normalizada y entrega de caso guardado, Aprobado |
| WB-02 | RF-01: pasos vacíos rechazados, Aprobado; rama 503 es garantía técnica adicional |
| BB-01, BB-03 | RF-01: salida válida / rechazo esperado vacío por interfaz CLI, Aprobado |
| WB-01, BB-02, BB-04 | Formato, JSON y recuperación/errores de dependencia: contrato técnico comprobado; sin RF/RNF directo bajo regla conservadora de M07 |

RF-02 (campaña/casos), RF-03 (ejecución/observaciones), RF-04 (resultado), RF-05
(evidencia), RF-06 (defecto), RF-07 (consulta de cadena) y RF-08 (conservar ejecuciones)
siguen sin verificación backend completa. HTML no se utiliza como sustituto.
RNF-01 integridad, RNF-02 consistencia de comparación, RNF-03 historia inmutable,
RNF-04 sustituibilidad de registro de ejecución y RNF-05 resultado inequívoco:
pendientes. Readonly de ManualCase no verifica historia de ejecuciones; Repository
de casos no demuestra RNF-04. No hay porcentaje de cobertura de código medido.

## Relación con SQA, sin modificar Semana 14

Fórmulas/fuente: metricas-calidad.md de #1516, commit d9956b09, consultado mediante git show.

| Métrica | Nueva observación y límites |
|---|---|
| M01 aprobadas/ejecutadas | Nueva suite 9/9=100%; regresión 17/17=100%, por separado; cero fallidas/bloqueadas en nueve casos; HTML sin nueva medición |
| M02 requisitos verificados/aplicables | 1/13=7.69% en backend CLI acotado; RF-01 continúa siendo el único completamente verificado en ese alcance; no aumenta por añadir más pruebas del mismo requisito |
| M05 controles de seguridad | S1 validación CLI revalidado. Sin nueva medición completa de los cinco controles; 20% de Semana 14 es antecedente, no mejora de autorización/tenant |
| M07 vínculo directo RF/RNF/casos revisados | Suite nueva: 6/9=66.67%, seis IDs de las primeras tres filas; otros tres técnicos. Universo distinto del 8/17=47.06% histórico: no presentar como incremento comparable ni cumplimiento de seis requisitos |

## Registro de defectos y seguimiento

No se detectaron defectos reales en los escenarios ejecutados; ningún caso quedó
Fallido, Bloqueado o No ejecutado en la suite nueva. No se crean IDs ficticios de defecto.
Tres fallas del mutante son deliberadas y están separadas en evidencia, no son regresiones.
REV-01–08 de #1512 permanecen abiertos: tenant, autorización, dominio completo,
API, representación, errores de arranque, persistencia e investigación asistiva.
No se verificó corrección de esos hallazgos y no se cierran AC por pasar estas pruebas.
Pendientes técnicos tras Semana 15: dominio/campañas/ejecuciones, contrato HTTP con
seguridad/tenant, evidencia privada/historial durable y pruebas de integración/asistivas.
Madelin Cerón mantiene sus pruebas y documentos; correcciones futuras y verificación
independiente: roles Pendiente de asignación. La entrega académica requiere revisión
y aprobación del PR; no acredita preparación productiva ni cierre del issue maestro.
