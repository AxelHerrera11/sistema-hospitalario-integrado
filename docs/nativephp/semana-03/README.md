# Investigacion y prototipo NativePHP - Semana 3

## Ficha de la asignacion

- **Estudiante:** Orellana Veliz, Gerson Giovanni (`gioore`)
- **Email:** gorellanav1@miumg.edu.gt
- **Modulo:** Investigacion y prototipo NativePHP para extension movil/escritorio
- **Semana:** Semana 3 - Diseno arquitectonico, vistas y patrones
- **Flujo:** evaluacion y prototipo acotado de un flujo HIS mediante NativePHP
- **Entregable / evidencia:** Vista arquitectonica C4/UML del modulo y dependencias.

## Resumen

Se inicia el microproyecto **Micro-HIS NativePHP Extension Prototype** en PHP 8.2+ vanilla. El prototipo simula una extension de escritorio/movil con acciones locales, cola pendiente, sincronizacion y telemetria sin usar servicios productivos ni datos clinicos reales.

## Archivos principales

- Microproyecto: `micro-his/nativephp-extension-prototype/`
- Pruebas: `micro-his/nativephp-extension-prototype/tests/run.php`
- Vista arquitectonica: [arquitectura-c4.md](./arquitectura-c4.md)
- Dependencias: [dependencias.md](./dependencias.md)
- Diagrama fuente: [fuentes/01-componentes.mmd](./fuentes/01-componentes.mmd)

## Ejecucion local

```bash
php micro-his/nativephp-extension-prototype/tests/run.php
```

Resultado esperado:

```text
OK: preparacion local, regla offline y error de persistencia verificados.
```

## Declaracion de uso de IA

Se uso Codex/OpenAI para estructurar la evidencia, crear el primer borrador del prototipo y redactar la documentacion. El estudiante debe revisar y validar el contenido antes de marcar la tarea como completada en Odoo.
