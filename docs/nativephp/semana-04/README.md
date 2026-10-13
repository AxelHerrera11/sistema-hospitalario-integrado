# Semana 4 - NativePHP para extension movil/escritorio

## Entregable

Propuesta de arquitectura en capas y repositorio para una extension NativePHP que consulte resultados pendientes sin duplicar reglas clinicas.

## Alcance del prototipo

- La interfaz de escritorio/movil consume un caso de uso compartido.
- El dominio sigue siendo responsable de estado, validacion y correccion controlada.
- El adaptador remoto usa HTTP; un adaptador InMemory permite demostracion offline.
- La evidencia es de diseno y simulacion. No se afirma una ejecucion real de NativePHP si el entorno no esta disponible.

## Evidencia

- `fuentes/01-capas-repositorio.mmd`: separacion de UI, aplicacion, dominio y adaptadores.
- `responsabilidades.md`: limites tecnicos y criterios de viabilidad.
