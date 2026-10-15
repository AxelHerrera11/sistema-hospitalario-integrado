# Signos vitales y alertas por valores anormales — Semana 3

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales
- **Semana:** Semana 3 — Diseño arquitectónico, vistas y patrones
- **Tarea individual:** Inicie «Micro-HIS Signos vitales y alertas por valores anormales» en PHP 8.2+ vanilla para ejecutar «captura de signos vitales, evaluación de umbrales y generación de alerta». Separe Presentation, Application, Domain y Persistence; use PDO con sentencias preparadas, sin framework, y pruebe al menos camino feliz, regla de dominio y error de persistencia mediante dobles o una base de prueba.
- **Entregable / evidencia:** Vista arquitectónica C4/UML del módulo y dependencias. Código en [`micro-his/signos-vitales/`](../../../micro-his/signos-vitales/).

## Resumen

El micro-HIS ejecuta el flujo con cuatro capas:

- **Presentation:** `VitalSignHttpHandler` construye la medición y llama a `RecordVitalSigns`.
- **Application:** el caso de uso verifica el episodio, evalúa los umbrales, guarda la medición con su auditoría y notifica la alerta después del `COMMIT`.
- **Domain:** valida la plausibilidad en `VitalSigns` y evalúa las reglas a través del contrato `VitalSignRule`.
- **Persistence:** implementa los puertos con PDO y sentencias preparadas.

La jerarquía de reglas es la que salió de aplicar LSP en la [Semana 2](../semana-02/solid-lsp.md).

## Archivos de evidencia

- [Vista arquitectónica C4/UML](./arquitectura-c4.md): contexto, contenedores, componentes y jerarquía de reglas.
- [Dependencias](./dependencias.md): regla entre capas, matriz del flujo y supuestos externos.
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Cómo verificar

```bash
php micro-his/signos-vitales/tests/run.php
```

Base de prueba: SQLite en memoria (`pdo_sqlite`) con `database/schema.sql` y datos ficticios. Resultado con PHP 8.3.33 (código de salida 0):

```text
OK: camino feliz: FC 138 + SpO2 94 -> 201 alerta critical con dos hallazgos
OK: camino feliz: alerta auditada en la transacción y notificada después
OK: camino feliz: FC 80 y peso 70 -> 201 sin alerta ni notificación
OK: RF-SV-03: solo FC 95 -> warning; la glucosa ausente no bloquea
OK: regla de dominio: sin signos -> 422
OK: regla de dominio: temperatura 50 °C -> 422 antes de evaluar
OK: regla de dominio: asignación terminada -> 403
OK: regla de dominio: episodio cerrado -> 409
OK: RF-SV-06: paciente de 9 años -> 201 THRESHOLDS_NOT_APPLICABLE, auditado y sin alerta
OK: validación: measured_at inválido -> 422
OK: LSP: las 6 reglas cumplen la misma prueba de contrato
OK: error de persistencia: auditoría caída -> 500, rollback y sin notificación
Todas las pruebas pasaron.
```

## Organización de la entrega

Para respetar el límite de 400 líneas por PR, la semana se entrega en tres ramas creadas desde `develop`, con archivos que no se solapan:

| Rama | Contenido | Orden de integración |
|---|---|---|
| `feature/asii-13-semana-03-signos-vitales-core-jhos-hgnu` | `bootstrap.php`, Domain y Application | 1 |
| `feature/asii-13-semana-03-signos-vitales-adapters-tests-jhos-hgnu` | Persistence, Presentation, `schema.sql` y pruebas | 2 |
| `feature/asii-13-semana-03-signos-vitales-arquitectura-jhos-hgnu` | Esta documentación | 3 |

Las pruebas se ejecutan cuando las ramas 1 y 2 están integradas.
