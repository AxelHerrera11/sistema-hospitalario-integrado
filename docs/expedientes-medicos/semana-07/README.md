# Semana 07 — Diseño de componentes y refactorización

**Tarea:** diseñar los componentes backend y frontend del expediente para «apertura y consulta autorizada del
expediente longitudinal» y refactorizar conceptualmente el punto con mayor acoplamiento entre expediente
longitudinal, episodios clínicos, acceso autorizado, integridad y datos sensibles, separando UI, aplicación, dominio
y persistencia sin ampliar el módulo.

## Entregables

| Archivo | Contenido |
|---|---|
| [`01-componentes.svg`](01-componentes.svg) · [`fuentes/01-componentes.mmd`](fuentes/01-componentes.mmd) | Componentes backend y frontend con interfaces provistas/requeridas |
| [`componentes.md`](componentes.md) | Contratos de entrada/salida y errores de cada componente |
| [`refactor.md`](refactor.md) + [`02-refactor-antes.svg`](02-refactor-antes.svg) / [`03-refactor-despues.svg`](03-refactor-despues.svg) | Comparación antes/después con medidas verificables |

## Ubicación del código

- Backend: `micro-his/expediente-medico/src/**`.
- Frontend: `micro-his/expediente-medico/public/ui/` (`app.js` aplicación, `estados.js` lógica pura, `vista.js`
  componentes visuales, `estilos.css`, `index.html`).

## Cómo verificar

```bash
php micro-his/expediente-medico/tests/run.php | grep -E "C7|R-0|N-07d"
node --test micro-his/expediente-medico/tests/ui-estados.test.mjs
```

## Estado

Implementado. El «antes» es conceptual y está marcado como tal; nunca existió en el repositorio.
