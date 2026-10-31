# Semana 04 — Arquitectura en capas y patrón repositorio

**Tarea:** continuar el mismo micro-HIS y el mismo flujo; organizar la entrada con MVC; definir una interfaz
Repository en el límite apropiado con adaptadores PDO e InMemory; el controlador no tendrá SQL ni reglas de negocio.
Además, analizar y diagramar cómo un repositorio de datos compartido integraría este módulo.

## Entregables

| Archivo | Contenido |
|---|---|
| [`capas-responsabilidades.md`](capas-responsabilidades.md) | MVC, contrato del Repository y sus dos adaptadores, objetos reutilizables |
| [`01-mvc-repository.svg`](01-mvc-repository.svg) · [`fuentes/01-mvc-repository.mmd`](fuentes/01-mvc-repository.mmd) | Clases reales de la entrada MVC y del Repository |
| [`repositorio-compartido.md`](repositorio-compartido.md) | Propiedad de datos en la base federada, reglas de integración y riesgos |
| [`02-repositorio-compartido.svg`](02-repositorio-compartido.svg) · [`fuentes/02-repositorio-compartido.mmd`](fuentes/02-repositorio-compartido.mmd) | Quién escribe y quién lee cada tabla |
| [`evidencias/mvc-repository.txt`](evidencias/mvc-repository.txt) | Salida real: mismo flujo con PDO e InMemory y reglas de arquitectura |

## Ubicación del código

- MVC: `micro-his/expediente-medico/src/Presentation/` (`Router`, `MedicalRecordController`, `JsonView`, `LongitudinalPresenter`, `Request`).
- Repository: `src/Application/MedicalRecordRepository.php`; adaptadores `src/Persistence/PdoMedicalRecordRepository.php`
  e `InMemoryMedicalRecordRepository.php`; `RowMapper.php` traduce filas a dominio.

## Cómo verificar

```bash
php micro-his/expediente-medico/tests/run.php | grep -E "\[(pdo|memoria)\] N-|R-0"
grep -rn "SELECT\|INSERT" micro-his/expediente-medico/src/Presentation   # sin resultados
```

## Estado

- **Implementado:** MVC, Repository y los dos adaptadores con pruebas idénticas.
- **Propuesta:** expediente entre hospitales vía MPI (no implementado).
