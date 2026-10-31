# Protocolo de revisión técnica — `PROPUESTO`

## Preparación

- Registrar commit/PR, alcance, base comparada y fecha de revisión.
- Confirmar que el material revisado no mezcla archivos de otro PR.
- Reunir contrato, decisiones de dominio, esquema vigente y pruebas realmente
  ejecutadas; marcar ausencias como pendientes.

## Revisión

1. Revisar arquitectura y propiedad de cada dependencia.
2. Seguir el flujo de creación, validación, rechazo y firma.
3. Verificar que todo 422 evita persistencia y que los errores externos son
   explícitos.
4. Revisar permisos, validación de entrada y exposición de datos.
5. Revisar accesibilidad del flujo implementado, si existe.
6. Asociar cada observación con archivo, línea o evidencia reproducible.

## Cierre

Registrar decisión por hallazgo: aceptado, corregir, diferir con responsable o
no aplicable con justificación. No convertir revisión pendiente en aprobación.
