# Primera evaluación parcial — presentación editable

## Diapositiva 1 — Portada y objetivo

**ASII-28 — QA funcional, pruebas manuales y matriz de regresión.**
Madelin Jazmín Cerón Molina · MadelinCeron · Semana 6 · Refs #12.
Defender decisiones y demostrar el núcleo de casos manuales con evidencia real.
Estado: operación CLI funcional; campañas y API REST siguen propuestas.

---

## Diapositiva 2 — Problema, actores y alcance

- QA necesita casos claros y trazabilidad futura entre campañas, resultados y defectos.
- Analista QA diseña/ejecuta; responsable de corrección consulta defectos (actores de Semana 1).
- Implementado: diseñar caso con propósito, pasos y esperado; guardar/recuperar por repositorio.
- Pendiente: campañas, ejecuciones, evidencias, defectos y matriz de regresión completa.

---

## Diapositiva 3 — Requisitos y aceptación

- RF-01: caso con propósito/pasos/esperado → guardado; vacío → rechazo.
- RF-02–08: campañas, ejecución/resultado, evidencia, defecto e historial: pendientes.
- RNF-01: vínculos; RNF-02: resultado coherente; RNF-03: historia inmutable.
- RNF-04: sustitución conforme a contrato; RNF-05: aprobado/fallido inequívocos.
- Evidencia actual de LSP: puerto de casos; no prueba todo el contrato de ejecución.

---

## Diapositiva 4 — Cuatro capas y diagrama

[Vista UML original](../semana-03/diagrama-arquitectura.puml): propuesta completa,
no despliegue. La implementación concreta solo su operación de diseño de caso.

```text
Presentation (CaseController / CaseView) → Application (DesignCase) → Domain
Persistence (InMemoryCaseRepository / PdoCaseRepository) → Domain (CaseRepository)
demo.php compone por constructor; Domain no conoce SQL ni framework.
```

MVC: controlador recibe, modelo valida/coordina, vista devuelve JSON.
PDO prepara consultas; bootstrap local carga clases sin Laravel.

---

## Diapositiva 5 — LSP y patrón Repository

- CaseRepository: save(ManualCase): void; find(string): ?ManualCase.
- Sustitutos: InMemory y PDO conservan datos y rechazan duplicado sin sobrescribir.
- La misma batería usa ambos sin modificar DesignCase ni CaseController.
- LSP prohíbe exigir entradas exclusivas u omitir garantías; StorageFailure es común.
- No hay subtipos Aprobado/Fallido con operaciones incompatibles; ese flujo aún no existe.

---

## Diapositiva 6 — Demostración funcional

Desde la raíz: `php micro-his/qa-regresion/tests/run.php`.
Alta CLI: `'{"purpose":"Evaluar caso QA","steps":"Abrir formulario","expected":"Campos visibles"}' | php micro-his/qa-regresion/demo.php`.
Resultado nuevo: status 201 y data con identidad; array/tipo inválido → 400;
propósito vacío → 422; PDO sin tabla → 503 seguro. La identidad cambia por alta.
[Comandos completos y salida](./evidencia-evaluacion.md); ninguna demo usa HTTP.

---

## Diapositiva 7 — REST e integración futura

- #1400 abierto: propone POST /api/v1/qa/manual-cases y GET /api/v1/qa/manual-cases/{id}.
- Endpoints HTTP QA implementados: **ninguno**; no confundir status CLI con HTTP.
- Prioridad: propiedad de datos, tenant, permisos propuestos y almacenamiento durable.
- Cliente futuro captura/muestra; servidor autentica, autoriza y aplica dominio/repositorios.
- Sin reintento automático de POST; falta idempotencia. Sin microservicio adicional injustificado.

---

## Diapositiva 8 — Resultados, riesgos y cierre de la exposición

- 29/10/2026, PHP 8.2.12: **17 pruebas aprobadas, 0 fallidas**, ejecutadas nuevamente.
- Demos verificadas: éxito, formato inválido, regla de dominio y fallo seguro de persistencia.
- SQLite en memoria: no conserva datos entre procesos, no es despliegue productivo.
- Próximo incremento: revisar seguridad/contrato, integrar por etapas y ampliar pruebas.
- Logrado: núcleo RF-01 verificable. Pendiente: flujo QA completo y revisión independiente.
