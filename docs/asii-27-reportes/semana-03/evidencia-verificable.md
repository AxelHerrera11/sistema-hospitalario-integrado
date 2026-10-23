# Evidencia verificable - ASII-27 Semana 3

**Módulo:** `ASII-27` - Reportes operativos | **Issue:** `#210`

## 1. Requisitos previos

```bash
php -v
```

## 2. Pruebas automatizadas

Desde `docs/asii-27-reportes/semana-03/micro-his/`:

```bash
php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
```

Salida esperada (5 pruebas, 0 fallidas):

```text
  [OK] dominio: rechaza rango de fechas invertido
  [OK] aplicacion: camino feliz filtra y exporta csv
  [OK] aplicacion: formato no soportado
  [OK] ocp: un exportador nuevo funciona sin tocar el caso de uso
  [OK] aplicacion: json agrega el total

Resumen: 5 pruebas, 0 fallidas.
```

## 3. Caso de uso por CLI (Presentation)

```bash
php bin/generar_reporte.php --desde=2026-10-01 --hasta=2026-10-31 --formato=csv            # OK, exit 0
php bin/generar_reporte.php --desde=2026-10-01 --hasta=2026-10-31 --formato=pdf            # 406, exit 2
php bin/generar_reporte.php --desde=2026-10-31 --hasta=2026-10-01 --formato=csv            # 422, exit 2
```

## 4. Validaciones ejecutadas

- `php -l` en cada archivo `.php`: sin errores de sintaxis.
- Runner de pruebas: `5 pruebas, 0 fallidas`.
- `src/Domain/` no contiene `PDO`, `$_GET`, `$_POST` ni `header(` (AC-06).

## 5. Nota de entorno

Los comandos se documentan para ejecutarse con PHP 8.2+ local; las capturas de su salida
deben adjuntarse antes de la entrega final.
