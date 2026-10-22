# Evidencia verificable — Semana 2 (SRP)

**Módulo:** `ASII-26 - Dashboard base UI/UX e implementación transversal` | **Issue:** `#209`
**Archivo de evidencia:** `evidencia/demo-srp.php` (autocontenido, solo PHP 8.2+).

## 1. Cómo ejecutar

Desde la raíz del repositorio:

```bash
php -l docs/asii-26-dashboard-base/semana-02/evidencia/demo-srp.php
php docs/asii-26-dashboard-base/semana-02/evidencia/demo-srp.php
```

## 2. Salida observada

```text
ANTES  admin: acceso=ok menu=panel|usuarios estado=datos
ANTES  medico: acceso=ok menu=panel estado=datos
DESPUÉS admin: acceso=ok menu=panel|usuarios estado=datos
DESPUÉS menu nuevo (controlador intacto): acceso=ok menu=panel|reportes estado=datos
DESPUÉS resumen vacio (loader nuevo): acceso=ok menu=panel estado=vacio
DESPUÉS sin sesion: login
DESPUÉS cambiar menu no altera la carga: OK
```

## 3. Lectura de la evidencia

- **Acceso (`AC-01`):** `DESPUÉS sin sesion: login` (sin sesión se resuelve el redireccionamiento a login).
- **Menú por rol (`AC-02`):** `admin` obtiene `panel|usuarios`; `medico` obtiene solo `panel`.
- **Estado (`AC-03`):** con datos resuelve `estado=datos`; con `ResumenVacio`, `estado=vacio`.
- **Extensión de menú (`AC-04`):** `MenuConReportes` añade `reportes` al menú sin editar `DashboardController`.
- **Extensión de carga (`AC-05`):** `ResumenVacio` cambia el estado sin editar `DashboardController`.
- **Aislamiento (`AC-06`):** `cambiar menu no altera la carga: OK`.

## 4. Validaciones ejecutadas

- `php -l`: `No syntax errors detected`.
- Ejecución del demo: salida de la sección 2 con `exit 0`.
- Render PlantUML de `fuentes/*.puml` → 2 SVG (exit 0) con PlantUML 1.2026.8.

## 5. Nota sobre los commits

Los commits separan las **clases nuevas** (`MenuConReportes`, `ResumenVacio`) del archivo del **coordinador** (`DashboardController`), de modo que puede verificarse que las extensiones no modifican el coordinador.
