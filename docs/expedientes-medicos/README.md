# Expediente médico electrónico base

Flujo de negocio de las 15 semanas: **apertura y consulta autorizada del expediente longitudinal** (episodios
clínicos, acceso autorizado, integridad y protección de datos sensibles).

## Dónde está cada cosa

| Parte | Ruta |
|---|---|
| Micro-HIS (PHP 8.2 vanilla, capas, MVC, Repository, PDO/InMemory, prototipo de interfaz) | [`micro-his/expediente-medico/`](../../micro-his/expediente-medico/README.md) |
| Contrato API | [`micro-his/expediente-medico/openapi.yaml`](../../micro-his/expediente-medico/openapi.yaml) |
| Adaptador Laravel (`GET /api/v1/patients/{code}/medical-record`) | `app/ExpedienteMedico/` |
| Prueba Feature del adaptador | `tests/Feature/ExpedienteMedico/` |
| Documentación por semana | `semana-01/` … `semana-15/` (tabla abajo) |
| Herramienta de diagramas | [`diagramas/`](diagramas/README.md) |
| Pendientes con otros módulos | [`pendientes-integracion.md`](pendientes-integracion.md) |

## Semanas

| Semana | Tema | Entregable principal |
|---|---|---|
| [01](semana-01/README.md) | UML | Casos de uso, actividad, secuencia y narrativa de alcance |
| [02](semana-02/README.md) | SOLID | RF/RNF, criterios de aceptación y DIP antes/después |
| [03](semana-03/README.md) | Arquitectura | Micro-HIS en capas, vistas C4/UML y dependencias |
| [04](semana-04/README.md) | Capas y Repository | MVC, Repository PDO/InMemory y repositorio compartido |
| [05](semana-05/README.md) | Cliente-servidor | Contrato API, frontera de microservicio medida y plan de PR |
| [06](semana-06/README.md) | 1.ª evaluación | Presentación, trazabilidad, matriz decisión→evidencia y cambio práctico |
| [07](semana-07/README.md) | Componentes | Diagrama de componentes, contratos y refactor |
| [08](semana-08/README.md) | UX | User flows por rol y 5 wireframes anotados |
| [09](semana-09/README.md) | Usabilidad y WCAG | Checklist, 8 hallazgos y backlog priorizado |
| [10](semana-10/README.md) | Movilidad | Propuesta 320–430 px, breakpoints y dos escenarios |
| [11](semana-11/README.md) | Prototipo | Prototipo navegable, mapa y capturas desktop/móvil |
| [12](semana-12/README.md) | 2.ª evaluación | Recorrido, matriz y cambio solicitado implementado |
| [13](semana-13/README.md) | Revisión formal | Plan, checklist de 15 ítems, roles y formato de hallazgos |
| [14](semana-14/README.md) | SQA | 7 métricas, riesgos, controles y calendario |
| [15](semana-15/README.md) | Pruebas | Matriz RF/RNF→prueba, unitarias, caja blanca/negra y defectos |
| [16](semana-16/README.md) | Integración | Validación on-premise, primera entrega funcional y DEF-EXP-10 corregido |

## Verificación rápida

```bash
php micro-his/expediente-medico/tests/run.php
node --test micro-his/expediente-medico/tests/ui-estados.test.mjs
php vendor/bin/phpunit tests/Feature/ExpedienteMedico
```

## Estado real

- **Implementado y probado:** apertura y consulta en el micro-HIS con RBAC, Gobernanza y Pacientes reales;
  adaptador Laravel de la consulta; prototipo de interfaz.
- **Provisional:** identidad por `X-User-ID` y membresías de demo en el micro-HIS; base SQLite en las pruebas.
- **Pendiente de otros:** permiso de apertura y criterio de enfermería (RBAC),
  formato común de errores, ruta en la SPA, validación en PostgreSQL. El provider ya está registrado en `bootstrap/providers.php`.
