# Micro-HIS Ingreso de resultados de laboratorio

Prototipo PHP 8.2+ vanilla para el flujo **captura y correccion controlada de resultados pendientes**.

## Capas

- `Presentation`: entrada HTTP/controlador ligero sin SQL ni reglas de dominio.
- `Application`: casos de uso y comandos.
- `Domain`: entidades, reglas, estados y excepciones.
- `Persistence`: adaptadores `PDO` e `InMemory` para el puerto de repositorio.

## Ejecutar pruebas

```bash
php micro-his/ingreso-resultados-lab/tests/run.php
```

## Evidencia cubierta

- Camino feliz: captura de resultado pendiente valido.
- Regla de dominio: unidad incompatible y correccion sin motivo.
- Error de persistencia: doble de repositorio que simula falla.

## Nota

Este microproyecto es intencionalmente independiente del Laravel principal para cumplir la restriccion de PHP vanilla sin framework.
