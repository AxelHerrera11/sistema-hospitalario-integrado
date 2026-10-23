# Evidencia verificable - ASII-26 Semana 3

**Módulo:** `ASII-26` - Dashboard base | **Issue:** `#209`

## 1. Requisitos previos

```bash
php -v
```

## 2. Pruebas automatizadas

Desde `docs/asii-26-dashboard-base/semana-03/micro-his/`:

```bash
php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
```

Salida esperada (5 pruebas, 0 fallidas):

```text
  [OK] dominio: rechaza valor negativo
  [OK] dominio: acepta valor cero
  [OK] aplicacion: camino feliz arma el resumen
  [OK] aplicacion: resumen vacio sin indicadores
  [OK] aplicacion: error de persistencia se propaga

Resumen: 5 pruebas, 0 fallidas.
```

## 3. Caso de uso por CLI (Presentation)

```bash
php bin/resumen_dashboard.php --sembrar   # OK, exit 0
```

Salida esperada:

```text
OK citas_hoy = 12 (Citas de hoy)
OK pacientes_activos = 42 (Pacientes activos)
OK docs_pendientes = 3 (Documentos pendientes)
```

## 4. Validaciones ejecutadas

- `php -l` en cada archivo `.php`: sin errores de sintaxis.
- Runner de pruebas: `5 pruebas, 0 fallidas`.
- `src/Domain/` no contiene `PDO`, `$_GET`, `$_POST` ni `header(` (AC-06).

## 5. Nota de entorno

Los comandos se documentan para ejecutarse con PHP 8.2+ local; las capturas de su salida
deben adjuntarse antes de la entrega final.
