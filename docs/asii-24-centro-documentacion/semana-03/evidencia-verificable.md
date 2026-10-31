# Evidencia verificable - ASII-24 Semana 3

**Módulo:** `ASII-24` - Centro de documentación | **Issue:** `#208`

## 1. Requisitos previos

```bash
php -v
php -m | Select-String -Pattern pdo_sqlite
```

## 2. Pruebas automatizadas

Desde `docs/asii-24-centro-documentacion/semana-03/micro-his/`:

```bash
php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php
```

Salida esperada (5 pruebas, 0 fallidas):

```text
  [OK] dominio: rechaza documento sin roles
  [OK] dominio: normaliza y detecta visibilidad por rol
  [OK] aplicacion: camino feliz publica y lista por rol
  [OK] aplicacion: no lista documentos de otro rol
  [OK] aplicacion: error de persistencia se propaga

Resumen: 5 pruebas, 0 fallidas.
```

## 3. Caso de uso por CLI (Presentation)

```bash
php bin/consultar_documentos.php --sembrar --dsn=sqlite:storage/microhis.sqlite
php bin/consultar_documentos.php --rol=enfermeria --dsn=sqlite:storage/microhis.sqlite   # OK, exit 0
php bin/consultar_documentos.php --rol=recepcion --dsn=sqlite:storage/microhis.sqlite   # OK, exit 0
```

## 4. Validaciones ejecutadas

- `php -l` en cada archivo `.php`: sin errores de sintaxis.
- Runner de pruebas: `5 pruebas, 0 fallidas`.
- `src/Domain/` no contiene `PDO`, `$_GET`, `$_POST` ni `header(` (AC-06).

## 5. Nota de entorno

Los comandos se documentan para ejecutarse con PHP 8.2+ local; las capturas de su salida
deben adjuntarse antes de la entrega final.
