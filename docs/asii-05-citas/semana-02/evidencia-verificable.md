# Evidencia verificable — Semana 2 (DIP)

**Módulo:** `ASII-05 - Gestión de citas` | **Issue:** `#207`
**Archivo de evidencia:** `evidencia/demo-dip.php` (autocontenido, solo PHP 8.2+).

## 1. Cómo ejecutar

Desde la raíz del repositorio:

```bash
php -l docs/asii-05-citas/semana-02/evidencia/demo-dip.php
php docs/asii-05-citas/semana-02/evidencia/demo-dip.php
```

## 2. Salida observada

```text
ANTES acoplado a Eloquent: requiere base de datos real (no inyectable)
DESPUÉS registrar cita: A-1 scheduled
DESPUÉS solape detectado: 409 appointments_overlap
DESPUÉS confirmar: confirmed
DESPUÉS cancelar: cancelled
DESPUÉS misma logica con otro adaptador: OK
```

## 3. Lectura de la evidencia

- **Registro (`AC-01`):** `DESPUÉS registrar cita: A-1 scheduled`.
- **Solape (`AC-02`):** `solape detectado: 409 appointments_overlap` (misma semántica que la exclusión GiST).
- **Confirmar/cancelar (`AC-03`):** `confirmar: confirmed` y `cancelar: cancelled`.
- **Inversión (`AC-05`):** `misma logica con otro adaptador: OK` (el controlador no cambia al sustituir el adaptador).
- **Sin ORM (`AC-06`):** en el diseño "antes" el servicio no puede operar sin base de datos (`no inyectable`).

## 4. Validaciones ejecutadas

- `php -l`: `No syntax errors detected`.
- Ejecución del demo: salida de la sección 2 con `exit 0`.
- Render PlantUML de `fuentes/*.puml` → 2 SVG (exit 0) con PlantUML 1.2026.8.

## 5. Nota sobre los commits

El commit del adaptador (`InMemoryAppointmentRepository`) es independiente del commit del controlador, de modo que puede verificarse que la sustitución del adaptador no modifica el código de alto nivel.
