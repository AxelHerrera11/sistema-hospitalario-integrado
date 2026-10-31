# Criterios de entrada y salida — `PROPUESTO`

## Entrada a validación

- Versión del código y contrato identificados.
- Dependencias y ambiente de prueba disponibles.
- Requisitos y decisiones clínicas aprobados.
- Casos de prueba vinculados con requisitos.

Si un componente no está integrado o su contrato no está decidido, registrar
el caso como pendiente; no simular un resultado.

## Salida de una ejecución

- Los casos planeados tienen resultado real y fuente de evidencia.
- Fallos están registrados con pasos de reproducción y no ocultos.
- Toda respuesta de rechazo se contrasta con la regla de no persistencia.
- Los hallazgos pendientes tienen responsable/decisión aprobada antes de
  cerrarse como resueltos.
- No se declara conformidad de seguridad o accesibilidad sin la verificación
  correspondiente.

No se definen umbrales numéricos ni se afirma que exista CI configurado.
