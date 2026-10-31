# Semana 16 (parte 3) — Pantalla de calidad de versiones en la aplicación

## Objetivo

Dar interfaz dentro de la aplicación a la API publicada en la parte 2 (`/api/v1/qa/runs`). Antes solo existía el
prototipo del micro-HIS con perfiles de demostración.

## Qué se agregó

| Pieza | Ubicación | Notas |
|---|---|---|
| Ruta `/calidad-versiones` y entrada «Calidad de versiones» | `resources/js/router` | `requiresAuth` y `qa.ejecuciones.ver` |
| Página | `resources/js/modules/qa-calidad/pages/QualityRunsPage.vue` | Planificar, listar, detalle, transiciones, resultados y quality gate |
| Lógica y pruebas | `resources/js/modules/qa-calidad/` | Reutiliza `view-model.js` del prototipo |

## Flujo en pantalla

1. Planificar (con `qa.ejecuciones.registrar`): versión SemVer, SHA de 40 caracteres, ambiente (local, ci o staging;
   producción no se permite) y suites. El plan por defecto trae las suites obligatorias unit y e2e que exige el dominio.
2. Iniciar → registrar el resultado de cada suite con su evidencia (ruta relativa, SHA-256 y tamaño) → completar,
   o abortar con motivo.
3. Evaluar el quality gate (con `qa.gates.evaluar`): veredicto Aprobado, Rechazado o Bloqueado, con el motivo principal
   y cada hallazgo.

Las acciones visibles salen de `allowedActions` del prototipo con los permisos efectivos de la sesión; el servidor
vuelve a exigir cada permiso. Las validaciones previas son las del prototipo; los errores 409/422 muestran el mensaje
del dominio y el resto, un mensaje por estado sin detalle interno.

## Validación

```bash
node --test "resources/js/modules/qa-calidad/*.test.mjs"   # pass 1, fail 0
node --test resources/js/router/navigation.test.mjs resources/js/router/permissions.test.mjs   # fail 0
npx vite build   # QualityRunsPage-*.js sin errores
```
