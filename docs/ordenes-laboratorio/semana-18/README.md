# Semana 18 — Evaluación final de órdenes de laboratorio

## Módulo Odoo

- Módulo: **Órdenes de laboratorio desde EMR**
- Estudiante: Javier Alexander Fajardo López (`Javifa2324`)
- Rama: `feature/asii-16-semana-18-evaluacion-ordenes-javifa2324`
- Destino: `develop`
- Dependencias: PR #1673 (Semana 16, ya fusionado en `develop`) y la revisión de la Semana 17
- Issue relacionado: `Refs #343`

## Objetivo

Cerrar la trazabilidad del flujo de órdenes sin declarar como terminada una función que
todavía no esté integrada. La evaluación cubre captura, persistencia, consulta, permisos,
aislamiento por hospital, errores y evidencia de despliegue.

| Paso | Evidencia de cierre | Criterio de no aprobación |
|---|---|---|
| Crear orden | respuesta y fila persistida | se guarda en sesión o en datos sembrados |
| Consultar orden | misma orden tras recargar | la captura y la consulta usan registros distintos |
| Permisos | rol autorizado y `403` para no autorizado | se usa `local-demo-user` |
| Tenant | datos del hospital correcto | se puede consultar otro tenant |
| Error | `400/403/404/409/422` documentado | se devuelve excepción interna |
| Operación real | demo contra servicios y PostgreSQL autorizados | solo simulación o mock |

## Comandos de verificación

```bash
php docs/ordenes-laboratorio/semana-18/evaluar_cierre.php
git diff --check
```

La salida `PENDIENTE` es válida mientras #1673 o la revisión de seguridad no estén
integrados. No se debe mover la tarea de Odoo a aprobada por esta documentación sola.

