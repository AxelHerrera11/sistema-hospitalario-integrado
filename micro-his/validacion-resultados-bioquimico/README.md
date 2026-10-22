# Micro-HIS Validacion de resultados por bioquimico

Prototipo PHP 8.2+ vanilla para el flujo **revision, validacion o rechazo de un resultado por bioquimico**.

## Capas

- `Presentation`: controlador ligero para entrada HTTP/formulario.
- `Application`: casos de uso para validar o rechazar.
- `Domain`: entidad revisable, estados y reglas clinicas.
- `Persistence`: adaptadores `PDO` e `InMemory`.

## Ejecutar pruebas

```bash
php micro-his/validacion-resultados-bioquimico/tests/run.php
```

## Evidencia cubierta

- Camino feliz: validacion de resultado pendiente.
- Regla de dominio: rechazo sin motivo y doble decision.
- Error de persistencia: repositorio doble que simula falla.

Este microproyecto es independiente del Laravel principal para evidenciar PHP vanilla sin framework.
