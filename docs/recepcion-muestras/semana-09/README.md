# Semana 9 — Evaluación de usabilidad y accesibilidad

**Módulo:** ASII-18, recepción de muestras y códigos identificadores.
**Autor:** María de los Ángeles López Fajardo, Mar-03.
**Issue:** [#6](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/6).
**Objetivo de la matriz:** Checklist, hallazgos y mejoras propuestas con método y evidencia explícitos.

## Índice

1. Introducción y problema.
2. Desarrollo del entregable.
3. Correspondencia con Laravel 12 y Vue.
4. Verificación y conclusión.
5. Fuentes y evidencia Git.

## Introducción y método

Se evalúa el flujo de recepción con inspección de código, prueba de navegador y análisis automático axe.
No se presenta una evaluación con usuarios o lectores de pantalla que no se haya realizado.
La unidad evaluada es el componente de recepción; el login/layout global requiere revisión transversal.

## Checklist de usabilidad y hallazgos

| Criterio | Hallazgo del flujo | Medida implementada | Evidencia |
|---|---|---|---|
| Visibilidad del estado | No confirmar si una recepción se guardó induce reenvío | busy, resultado textual y recuperación con clave | Navegador: corte de red. |
| Correspondencia con tarea | Era necesario conocer el ítem sin consultar su referencia | Búsqueda de pendientes con requisitos | API y navegador: búsqueda/selección. |
| Prevención de errores | Copiar la identidad esperada impediría verificar etiqueta | Selección no autocompleta identidad observada | Navegador: patient vacío. |
| Control del usuario | Reimpresión podía confundirse con nueva recepción | Acción de etiqueta mediante GET autorizado | API etiqueta y conteo de POST. |
| Consistencia | Lector veía acciones que no podía ejecutar | Visibilidad por permisos efectivos | Navegador modo reader y HTTP 403. |
| Reconocimiento | Un código largo podía quedar cortado | Etiqueta ajusta línea y conserva texto completo | Capturas móvil/escritorio. |
| Recuperación | Un GET fallido bloqueaba nueva recepción | Incertidumbre solo tras operación que pudo guardar | Regresión de navegador. |
| Prevención entre pacientes | Nueva muestra podía reutilizar datos anteriores | Limpieza de identificación y búsqueda | Navegador y foco en item. |

## Checklist de accesibilidad

| Elemento | Implementación/verificación | Estado |
|---|---|---|
| Nombre accesible de campos | label/for y legend para grupo | Revisado en DOM y axe. |
| Orden de navegación | Inputs y botones en secuencia del flujo | Automatizado parcialmente; teclado completo manual pendiente. |
| Foco tras error | Resumen con tabindex=-1 y focus después del render | Implementado y revisado en fuente. |
| Mensajes dinámicos | role=alert, role=status y aria-live | Presente; lector de pantalla manual pendiente. |
| Estado no dependiente del color | Texto accepted/rejected y motivo | Verificado visualmente. |
| Contraste automático | axe sobre formulario | Resultado en navegador.txt; no certificación integral. |
| Reflow móvil | Cuatro viewports sin overflow | Automatizado y captura inspeccionada. |
| Zoom 200%/400% | No ejecutado como evaluación manual completa | Pendiente. |
| Impresión y lector de pantalla | Botón y etiqueta accesibles | Vista/contrato verificados; hardware/NVDA pendientes. |

## Registro de defectos y decisiones

UX-01: no distinguir POST incierto de GET fallido dejaba el formulario bloqueado.
Corrección: perform recibe mayHaveSaved; solo recibir/recuperar pueden crear incertidumbre.
UX-02: el rol lector recibía formulario y descubría la denegación al enviar.
Corrección: permisos efectivos controlan las acciones visibles; los middleware conservan la protección real.
UX-03: faltaba una forma de localizar el ítem y consultar sus requisitos.
Corrección: búsqueda acotada al hospital y estado pendiente, manteniendo la verificación de identidad independiente.

## Mejoras propuestas priorizadas

| Prioridad | Mejora pendiente | Aceptación |
|---|---|---|
| Alta | Evaluación con lector de pantalla | Recorrer búsqueda, error, rechazo y recuperación sin perder contexto. |
| Alta | Reconciliación entre sesiones tras cerrar pestaña | Recuperar una solicitud sin guardar información clínica en almacenamiento inseguro. |
| Media | Mensajes legibles para motivos controlados | Conservar códigos de API, traducir presentación sin cambiar decisiones. |
| Media | Evaluación con técnico real y muestras ficticias | Registrar tiempo, errores y observaciones sin afirmar resultados antes de ejecutarla. |
| Media | Errores enlazados por campo | Resumen navega al campo y describe corrección sin depender de color. |

## Reproducción y alcance de la evidencia

tests/Browser/Reception/run.cjs monta el componente real con dobles HTTP declarados.
Usa el navegador para búsqueda, recuperación, impresión, consulta y modo lector.
La autorización y persistencia se prueban por separado con JWT y PostgreSQL reales.
La salida de axe solo permite afirmar cero violaciones detectadas en el estado probado.
No permite declarar 100% WCAG ni una tasa de éxito de usuarios.

## Conclusión

Los hallazgos originaron cambios en el producto y casos de regresión, no solo una lista de recomendaciones.
El checklist conserva los puntos manuales pendientes para que una revisión posterior pueda cerrarlos con evidencia.

## Fuentes y evidencia Git

- [README original y matriz ASII-18](../../../README.md).
- [Declaración de IA](../DECLARACION_IA.md) y [trazabilidad final](../semana-15/README.md).
- [Índice de ramas y PR a develop](../semana-15/ramas-prs.md).
- Rama: feature/asii-18-semana-09-recepcion-muestras-mar-03.
- Los archivos de código enlazados pertenecen al HIS real; el micro conserva su carpeta independiente.
