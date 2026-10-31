# Semana 06 — Primera evaluación parcial

**Tarea:** defender la arquitectura de «apertura y consulta autorizada del expediente longitudinal» conectando
actores, UML, RF/RNF, principio SOLID, capas, Repository y contrato API, y responder un cambio práctico considerando
expediente longitudinal, episodios clínicos, acceso autorizado, integridad y datos sensibles.

## Entregables

| Archivo | Contenido |
|---|---|
| [`presentacion.md`](presentacion.md) | 8 diapositivas: portada + 7 de contenido (formato Marp: `---` separa diapositivas) |
| [`01-trazabilidad.svg`](01-trazabilidad.svg) · [`fuentes/01-trazabilidad.mmd`](fuentes/01-trazabilidad.mmd) | Actor → caso de uso → requisito → decisión → prueba |
| [`matriz-decision-evidencia.md`](matriz-decision-evidencia.md) | D-01…D-11 con alternativa descartada y evidencia |
| [`cambio-practico.md`](cambio-practico.md) | Cambio real aplicado: identidad CENTRAL vs. referencia local del hospital |

## Cómo verificar

```bash
php micro-his/expediente-medico/tests/run.php | grep -E "C8|Resumen"
php vendor/bin/phpunit tests/Feature/ExpedienteMedico
```

Para exportar la presentación a PDF con Marp (opcional, requiere descargar la herramienta):
`npx @marp-team/marp-cli docs/expedientes-medicos/semana-06/presentacion.md --pdf`.

## Estado

Todas las decisiones D-01…D-11 tienen evidencia ejecutable. Los supuestos S-01 y S-02 siguen pendientes de RBAC.
