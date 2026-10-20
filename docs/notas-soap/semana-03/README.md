# Notas SOAP y diagnósticos — Semana 3

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-11 — Notas SOAP y diagnósticos
- **Semana:** Semana 3 — Diseño arquitectónico, vistas y patrones
- **Tarea individual:** Inicie «Micro-HIS Notas SOAP y diagnósticos» en PHP 8.2+ vanilla para ejecutar «registro de nota SOAP y asociación de diagnóstico clínico». Separe Presentation, Application, Domain y Persistence; use PDO con sentencias preparadas, sin framework, y pruebe al menos camino feliz, regla de dominio y error de persistencia mediante dobles o una base de prueba.
- **Entregable / evidencia:** Vista arquitectónica C4/UML del módulo y dependencias. Código en [`micro-his/notas-soap/`](../../../micro-his/notas-soap/).

## Resumen

El micro-HIS ejecuta el flujo con cuatro capas:

- **Presentation:** `SoapNoteHttpHandler` rechaza con `422 PAYLOAD_INVALID` el encabezado o los campos que no son texto, construye los objetos de valor y llama a los casos de uso `RegisterSoapNote` y `AssociateDiagnosis`.
- **Application:** los casos de uso coordinan el acceso (`TreatingPhysicianGuard`), el catálogo CIE-10 y la persistencia mediante puertos.
- **Domain:** valida las secciones y aplica las reglas clínicas en `DiagnosisPolicy`, sin dependencias.
- **Persistence:** implementa los puertos con PDO y sentencias preparadas.

Es la división por responsabilidades que salió de aplicar SRP en la [Semana 2](../semana-02/solid-srp.md).

## Archivos de evidencia

- [Vista arquitectónica C4/UML](./arquitectura-c4.md): contexto, contenedores, componentes y clases.
- [Dependencias](./dependencias.md): regla entre capas, matriz caso de uso → dependencias y supuestos externos.
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Cómo verificar

```bash
php micro-his/notas-soap/tests/run.php
```

Base de prueba: SQLite en memoria (`pdo_sqlite`) con `database/schema.sql` y datos ficticios, más un doble de `Icd10Catalog` que simula la caída del catálogo. Resultado con PHP 8.3.33 (código de salida 0):

```text
OK: camino feliz: nota SOAP 201 en borrador con auditoría
OK: camino feliz: diagnóstico principal J18.9 201 con descripción del catálogo
OK: regla de dominio: sección vacía -> 422
OK: regla de dominio: asignación terminada -> 403
OK: regla de dominio: código repetido -> 409
OK: regla de dominio: segundo principal -> 409
OK: regla de dominio: código inactivo en el catálogo -> 422
OK: validación: tipo de diagnóstico no permitido -> 422
OK: validación de tipos: sección no string o encabezado no string -> 422 sin guardar
OK: validación de tipos: código o tipo de diagnóstico no string -> 422 sin guardar
OK: regla de dominio: nota firmada no acepta diagnósticos -> 409
OK: regla de dominio pura (sin BD): la política rechaza un segundo principal
OK: doble de prueba: catálogo caído -> 503 y la nota queda sin diagnósticos
OK: error de persistencia: auditoría caída -> 500 y rollback de la nota
Todas las pruebas pasaron.
```

## Organización de la entrega

Para respetar el límite de 400 líneas por PR, la semana se entrega en tres ramas creadas desde `develop`, con archivos que no se solapan:

| Rama | Contenido | Orden de integración |
|---|---|---|
| `feature/asii-11-semana-03-notas-soap-core-jhos-hgnu` | `bootstrap.php`, Domain y Application | 1 |
| `feature/asii-11-semana-03-notas-soap-adapters-tests-jhos-hgnu` | Persistence, Presentation, `schema.sql` y pruebas | 2 |
| `feature/asii-11-semana-03-notas-soap-arquitectura-jhos-hgnu` | Esta documentación | 3 |

Las pruebas se ejecutan cuando las ramas 1 y 2 están integradas.
