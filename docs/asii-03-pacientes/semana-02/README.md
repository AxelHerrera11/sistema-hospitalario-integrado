# ASII-03 — Pacientes: registro, edición, búsqueda y detalle

## Semana 2 — Proceso, modelo de diseño y principio SOLID

**Estudiante:** Madelin Jazmín Cerón Molina  
**GitHub:** `@MadelinCeron`  
**Módulo:** ASII-03 — Pacientes  
**Principio SOLID asignado:** LSP — Liskov Substitution Principle  
**Proceso:** registro, búsqueda y actualización segura de un paciente.

## Objetivo

Definir los requisitos y criterios de aceptación del flujo de pacientes y proponer un diseño que aplique el principio de sustitución de Liskov.

La propuesta busca que distintas implementaciones de las operaciones de pacientes puedan sustituirse sin alterar el comportamiento esperado por el código que las utiliza.

## Alcance

Esta entrega incluye:

- Requisitos funcionales.
- Requisitos no funcionales.
- Criterios de aceptación.
- Análisis del diseño antes de aplicar LSP.
- Diseño propuesto aplicando LSP.
- Identificación de responsabilidades y dependencias.
- Ejemplo de sustitución entre implementaciones.
- Evidencia verificable basada en la estructura actual del repositorio.

El flujo considerado es:

1. Registro de pacientes.
2. Búsqueda de pacientes.
3. Actualización segura de información.

## Fuera de alcance

En esta entrega no se implementan:

- Controllers.
- Services funcionales.
- Endpoints API.
- Interfaz de usuario.
- Nuevas migraciones.
- Pruebas automatizadas.
- Integraciones con otros módulos clínicos.

La Semana 2 corresponde al análisis de requisitos y diseño.

## Principio LSP

El principio de sustitución de Liskov establece que una implementación debe poder sustituir a otra que cumpla el mismo contrato sin cambiar el comportamiento esperado del sistema.

Aplicado al módulo de pacientes, las operaciones de registro, búsqueda y actualización deben conservar las mismas reglas y resultados esperados aunque la implementación concreta cambie.

Una implementación no debería:

- exigir condiciones adicionales al cliente;
- devolver resultados incompatibles;
- eliminar validaciones definidas por el contrato;
- producir efectos secundarios inesperados;
- cambiar las garantías de las operaciones.

## Documentación

### Requisitos y criterios

Los requisitos funcionales, no funcionales y criterios de aceptación se encuentran en:

`rf-rnf-criterios.md`

### Aplicación de LSP

El análisis del diseño antes y después de aplicar el principio se encuentra en:

`solid-lsp.md`

### Evidencia

Las fuentes revisadas y los escenarios utilizados para verificar la propuesta se encuentran en:

`evidencia-verificable.md`

## Estructura de la entrega

```text
docs/asii-03-pacientes/semana-02/
├── README.md
├── rf-rnf-criterios.md
├── solid-lsp.md
└── evidencia-verificable.md
```

## Resultado esperado

Al finalizar esta entrega se cuenta con una propuesta de diseño para el flujo de pacientes que:

- mantiene responsabilidades definidas;
- permite sustituir implementaciones compatibles;
- conserva las reglas del flujo;
- evita dependencias innecesarias;
- establece criterios claros para validar el comportamiento esperado.

No se presenta esta propuesta como funcionalidad ya implementada.
