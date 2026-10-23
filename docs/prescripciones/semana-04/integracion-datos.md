# Integración con datos compartidos

Estado: `PROPUESTO`; confirmar propiedad de datos, límites de tenant y
disponibilidad de cada módulo antes de implementar.

## Límites sugeridos

- Prescripciones es responsable de sus propios registros y transiciones.
- El catálogo de medicamentos, las alergias y la auditoría se consultan por
  puertos; Prescripciones no debe escribir directamente en tablas propiedad
  de esos módulos.
- La conexión hospitalaria y la configuración de PDO se inyectan en
  infraestructura. Los consumidores no crean conexiones paralelas.
- Los identificadores de paciente y profesional son referencias; las reglas
  de pertenencia y autorización se resuelven en los límites correspondientes.
- La disponibilidad de dependencias y los fallos de almacenamiento se
  presentan explícitamente; no se convierten en datos vacíos.

## Integridad

- Validar contexto de tenant antes de leer o escribir información clínica.
- Persistir solo después de que las validaciones clínicas permitan continuar.
- Mantener el rechazo de SD-PRE-01 sin efectos de escritura.
- Acordar transacciones y consistencia entre límites antes de introducir
  efectos distribuidos.

Este análisis no determina un esquema compartido ni una estrategia de
microservicios.
