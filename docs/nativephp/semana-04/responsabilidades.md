# Responsabilidades y viabilidad

| Componente | Responsabilidad |
|---|---|
| NativePHP shell | Ciclo de vida de ventana, navegacion y almacenamiento local minimo |
| ViewModel | Transformar estado de aplicacion en datos para la vista |
| Application | Orquestar consulta y reintentos sin reglas clinicas duplicadas |
| Domain | Validar estado pendiente y restricciones de correccion |
| Repository port | Ocultar si la fuente es API o InMemory |

## Criterio de viabilidad

La simulacion con `InMemoryRepository` demuestra el flujo de dependencias, pero no equivale a ejecutar NativePHP. La viabilidad tecnica queda condicionada a disponer de PHP 8.2, extensiones requeridas, Composer y un entorno NativePHP funcional.
