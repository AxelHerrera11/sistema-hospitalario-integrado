# Evidencia verificable — Semana 2 (ISP)

**Módulo:** `ASII-24 - Centro de documentación y manuales por rol` | **Issue:** `#208`
**Archivo de evidencia:** `evidencia/demo-isp.php` (autocontenido, solo PHP 8.2+).

## 1. Cómo ejecutar

Desde la raíz del repositorio:

```bash
php -l docs/asii-24-centro-documentacion/semana-02/evidencia/demo-isp.php
php docs/asii-24-centro-documentacion/semana-02/evidencia/demo-isp.php
```

## 2. Salida observada

```text
ANTES CatalogoController declara 7 metodos (5 sin uso)
ANTES invocar metodo no usado: no soportado
DESPUÉS CatalogoController declara 2 metodos (listar|buscar)
DESPUÉS listar para enfermeria: Manual de enfermeria
DESPUÉS listar para admin: Protocolo de admision
DESPUÉS buscar "protocolo" como admin: Protocolo de admision
DESPUÉS publicar (otro contrato, 2 metodos): 1 documento
DESPUÉS ningun cliente implementa metodos ajenos: OK
```

## 3. Lectura de la evidencia

- **Interfaz "gorda" (`AC-05`):** el catálogo declara 7 métodos, de los cuales 5 no usa, y al invocarlos lanzan `no soportado`.
- **Segregación (`AC-05`):** con interfaces segregadas, `CatalogoController` declara solo 2 métodos (`listar`, `buscar`).
- **Catálogo por rol (`AC-02`):** `enfermeria` ve "Manual de enfermeria"; `admin` ve "Protocolo de admision".
- **Búsqueda (`AC-03`):** buscar "protocolo" como `admin` devuelve el documento visible para ese rol.
- **Publicación (`AC-01`):** el cliente de registro publica usando otro contrato de 2 métodos.
- **Sin métodos ajenos (`AC-06`):** `ningun cliente implementa metodos ajenos: OK`.

## 4. Validaciones ejecutadas

- `php -l`: `No syntax errors detected`.
- Ejecución del demo: salida de la sección 2 con `exit 0`.
- Render PlantUML de `fuentes/*.puml` → 2 SVG (exit 0) con PlantUML 1.2026.8.

## 5. Nota sobre los commits

Los commits separan los **contratos segregados** (`ConsultaDocumentos`, `RegistroDocumentos`) del archivo del **cliente** (`CatalogoController`), de modo que puede verificarse que el cliente depende de la interfaz mínima y no de la "gorda".
