# Evidencia verificable - Semana 3 (Micro-HIS en capas)

**Modulo:** `ASII-05` - Gestion de citas | **Issue:** `#207`

## 1. Requisitos previos

```bash
php -v
php -m | grep -i pdo_sqlite
```

## 2. Pruebas automatizadas

Desde `docs/asii-05-citas/semana-03/micro-his/`:

```bash
php tests/ejecutar_pruebas.php
```

Salida esperada (5 pruebas, 0 fallidas):

```text
  [OK] dominio: rechaza rango de fecha invertido
  [OK] dominio: confirma, cancela y detecta solape
  [OK] aplicacion: camino feliz agenda
  [OK] aplicacion: rechaza solape sin persistir
  [OK] aplicacion: error de persistencia se propaga

Resumen: 5 pruebas, 0 fallidas.
```

## 3. Caso de uso por CLI (Presentation)

```bash
php bin/agendar_cita.php --paciente=P-1 --doctor=D-1 --desde=2026-10-21T09:00 --hasta=2026-10-21T09:30   # OK, exit 0
php bin/agendar_cita.php --paciente=P-1 --doctor=D-9 --desde=2026-10-21T09:15 --hasta=2026-10-21T09:45   # solape, exit 2
php bin/agendar_cita.php --paciente=P-2 --doctor=D-2 --desde=2026-10-21T10:00 --hasta=2026-10-21T09:00   # rango invalido, exit 2
```

## 4. Validaciones ejecutadas

- `php -l` en cada archivo `.php`: sin errores de sintaxis.
- Runner de pruebas: `5 pruebas, 0 fallidas`.
- `src/Domain/` no contiene `PDO`, `$_GET`, `$_POST` ni `header(` (AC-06).

## 5. Nota de entorno

Los comandos se documentan para ejecutarse con PHP 8.2+ local; las capturas de su salida
deben adjuntarse antes de la entrega final.
