# Ingreso de resultados de laboratorio - Semana 3

## Ficha de la asignacion

- **Estudiante:** Orellana Veliz, Gerson Giovanni (`gioore`)
- **Email:** gorellanav1@miumg.edu.gt
- **Modulo:** Ingreso de resultados de laboratorio
- **Semana:** Semana 3 - Diseno arquitectonico, vistas y patrones
- **Flujo:** captura y correccion controlada de resultados pendientes
- **Entregable / evidencia:** Vista arquitectonica C4/UML del modulo y dependencias.

## Resumen

Se inicia el microproyecto **Micro-HIS Ingreso de resultados de laboratorio** en PHP 8.2+ vanilla. El prototipo separa Presentation, Application, Domain y Persistence, usa un puerto de repositorio, incluye adaptadores `PDO` e `InMemory`, y valida camino feliz, regla de dominio y error de persistencia.

## Archivos principales

- Microproyecto: `micro-his/ingreso-resultados-lab/`
- Pruebas: `micro-his/ingreso-resultados-lab/tests/run.php`
- Vista arquitectonica: [arquitectura-c4.md](./arquitectura-c4.md)
- Dependencias: [dependencias.md](./dependencias.md)
- Diagrama fuente: [fuentes/01-componentes.mmd](./fuentes/01-componentes.mmd)

## Ejecucion local

```bash
php micro-his/ingreso-resultados-lab/tests/run.php
```

Resultado esperado:

```text
OK: camino feliz, regla de dominio y error de persistencia verificados.
```

## Declaracion de uso de IA

Se uso Codex/OpenAI para estructurar la evidencia, crear el primer borrador del prototipo y redactar la documentacion. El estudiante debe revisar y validar el contenido antes de marcar la tarea como completada en Odoo.
