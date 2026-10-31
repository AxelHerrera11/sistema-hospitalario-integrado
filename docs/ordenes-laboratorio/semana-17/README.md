# Semana 17 — Seguridad y despliegue de órdenes de laboratorio

## Módulo Odoo

- Módulo: **Órdenes de laboratorio desde EMR**
- Estudiante: Javier Alexander Fajardo López (`Javifa2324`)
- Rama: `feature/asii-16-semana-17-seguridad-ordenes-javifa2324`
- Destino: `develop`
- Dependencia: PR #1673 (Semana 16), ya fusionado en `develop`
- Issue relacionado: `Refs #343`

## Alcance

Esta entrega deja la matriz de seguridad y la comprobación reproducible para la operación
de órdenes. La Semana 16 (#1673) ya está integrada en `develop`; esta evidencia no afirma que un entorno de staging ya esté desplegado.

| Control | Evidencia esperada | Resultado en este checkout |
|---|---|---|
| Identidad | usuario autenticado, no usuario demo | integrado en #1673; falta prueba contra PostgreSQL |
| Tenant | `X-Tenant-ID` validado y aislado | integrado en #1673; falta prueba contra PostgreSQL |
| Permiso | `laboratorio.ordenes.ver` y `laboratorio.ordenes.crear` | integrado en #1673; falta prueba contra PostgreSQL |
| Persistencia | PDO/hospital y transacción | integrado en #1673; falta prueba contra PostgreSQL |
| Errores | `error.code` y `meta.request_id` sin excepciones internas | integrado en #1673; falta prueba contra PostgreSQL |
| Datos clínicos | sin credenciales ni datos reales en evidencia | verificado |

## Criterios de aceptación

1. La pantalla no muestra órdenes de otro hospital.
2. Un usuario sin permiso recibe `403` y un usuario sin tenant recibe `400 TENANT_REQUIRED`.
3. Una orden válida queda persistida y puede recuperarse tras una nueva sesión.
4. Un fallo de persistencia no deja una orden parcialmente creada.
5. La evidencia de despliegue se marca como pendiente si no existe un entorno real verificable.

## Validación

```bash
php docs/ordenes-laboratorio/semana-17/verificar_seguridad.php
git diff --check
```

El verificador distingue entre archivos presentes y funcionalidad realmente integrada.
No reemplaza pruebas de integración contra PostgreSQL ni una demostración en staging.

