# Declaración de uso de inteligencia artificial - Semana 2

**Herramienta:** Claude Code con el modelo Claude Opus 5.5 (`claude-opus-5-5`).

**Propósito:** revisar las instrucciones, plantillas y evidencia del repositorio (Semana 1, `config/rbac.php`, estado del slice H5, documentación de Prescripciones), redactar RF/RNF, criterios, supuestos y el análisis ISP; escribir las fuentes PlantUML y el script de evidencia, y ejecutar las validaciones locales.

**Antecedente reutilizado:** la entrega personal previa de Semana 2 de la estudiante (repositorio `ASII-Tareas-Yamilet1235`, en PDF). Se conservaron sus escenarios de alta, duplicado, búsqueda y vigencia, y su idea de separar búsqueda, registro y vigencia. Identidad, campos y aislamiento se ajustaron a la Semana 1 de este repositorio: código provisional y base por hospital, no `tenant_id`.

**Contenido generado o modificado:** todos los archivos de esta carpeta son borradores generados con IA. Se descartaron requisitos sin respaldo, como la paginación y el aislamiento por `tenant_id`, y los supuestos se marcaron como tales. En el diseño ISP se descartó una interfaz por método y se excluyó de los contratos propios el `MedicationCatalog` de Prescripciones, que pertenece a ese módulo.

**Validación:** los comandos y resultados de [EVIDENCIA.md](./EVIDENCIA.md) y de los PR se ejecutaron realmente. La revisión humana de la estudiante queda **pendiente** y no se declara realizada.
