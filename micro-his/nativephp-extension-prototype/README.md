# Micro-HIS NativePHP Extension Prototype

Prototipo PHP 8.2+ vanilla para evaluar un flujo HIS acotado que podria ejecutarse como extension de escritorio/movil con NativePHP.

## Capas

- `Presentation`: controlador de acciones del prototipo.
- `Application`: casos de uso para preparar acciones y sincronizarlas.
- `Domain`: cola local, conectividad, reglas de sincronizacion y hallazgos.
- `Persistence`: adaptadores `PDO` e `InMemory`.

## Ejecutar pruebas

```bash
php micro-his/nativephp-extension-prototype/tests/run.php
```

## Evidencia cubierta

- Camino feliz: preparar accion local y sincronizarla.
- Regla de dominio: no sincronizar cuando el prototipo esta offline.
- Error de persistencia: doble de repositorio que simula falla al guardar.

El prototipo no usa servicios productivos ni datos clinicos reales.
