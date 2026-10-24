# Matriz de riesgos y controles

Escala: probabilidad (P) e impacto (I) de 1 a 5; exposición = P x I. Revisar después de cada incidente o cambio de integración.

| ID | Riesgo | P | I | Exposición | Controles preventivos/detectivos | Respuesta y propietario |
|---|---|---:|---:|---:|---|---|
| R-01 | Contar una cama dos veces por estancias superpuestas | 3 | 5 | 15 | Invariante, restricciones y pruebas de solapamiento | Bloquear entrega; autor coordina con ASII-07/08 |
| R-02 | Mostrar cero cuando PostgreSQL falla | 2 | 5 | 10 | Excepción a `503`, prueba de falla y alerta | Ocultar métricas/reintentar; autor backend |
| R-03 | Mezclar hospitales por tenant incorrecto | 2 | 5 | 10 | Tenant obligatorio y pruebas cruzadas | Revocar despliegue e investigar; seguridad/líder |
| R-04 | Exponer paciente o episodio en agregado/log | 2 | 5 | 10 | Contrato mínimo y revisión de payload/log | Retirar evidencia y tratar incidente; seguridad |
| R-05 | Fotografía incoherente durante traslados | 3 | 4 | 12 | Transacción repetible y corte único | Reintentar/ajustar consulta; backend/DBA |
| R-06 | Dato obsoleto interpretado como actual | 3 | 4 | 12 | Corte, antigüedad, rótulo y M-09 | Ocultar o marcar; frontend/operación |
| R-07 | Consulta degrada con crecimiento de camas | 3 | 3 | 9 | Índices, EXPLAIN y M-07 | Optimizar o limitar; backend/DBA |
| R-08 | Usuario móvil o de teclado no completa tarea | 3 | 3 | 9 | M-10/M-12 y revisión accesible | Corregir antes de producción; frontend/QA |
| R-09 | Contrato de ASII-06/07/08 cambia sin aviso | 3 | 4 | 12 | Revisión de integración y pruebas de contrato | Coordinar migración; líder y módulos dueños |
| R-10 | Métricas o logs contienen secretos | 2 | 5 | 10 | Lista permitida, sanitización y revisión | Rotar secreto/eliminar exposición; operación |
| R-11 | Prototipo se confunde con frontend productivo | 3 | 2 | 6 | Rótulo visible y documentación | Corregir evidencia; autor |
| R-12 | Prueba solo InMemory oculta diferencia PostgreSQL | 4 | 4 | 16 | Integración obligatoria antes de Semana 16 | Bloquear entrega funcional; QA/backend |

## Priorización

- `15-25`: crítico, plan y responsable antes de continuar.
- `8-14`: alto, control obligatorio en la entrega afectada.
- `4-7`: medio, seguimiento en revisión semanal.
- `1-3`: bajo, aceptar o vigilar con justificación.

La exposición residual se registra después de ejecutar controles; no se reduce por declaración sin evidencia.
