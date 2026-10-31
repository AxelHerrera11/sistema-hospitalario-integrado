# Semana 03 — Diseño arquitectónico, vistas y patrones

**Tarea:** iniciar el «Micro-HIS Expediente médico electrónico base» en PHP 8.2+ vanilla para el flujo «apertura y
consulta autorizada del expediente longitudinal», separando Presentation, Application, Domain y Persistence, con PDO
y sentencias preparadas, sin framework, y probar camino feliz, regla de dominio y error de persistencia.

## Entregables

| Archivo | Contenido |
|---|---|
| [`arquitectura.md`](arquitectura.md) | Decisiones, vistas y pruebas exigidas |
| [`01-c4-contexto.svg`](01-c4-contexto.svg), [`02-c4-contenedores.svg`](02-c4-contenedores.svg), [`03-componentes-capas.svg`](03-componentes-capas.svg) | Vistas C4/UML (fuentes en [`fuentes/`](fuentes/)) |
| [`dependencias.md`](dependencias.md) | Dependencias reales, provisionales e inversas |
| [`evidencias/pruebas-semana-03.txt`](evidencias/pruebas-semana-03.txt) | Salida real de las pruebas de camino feliz, regla de dominio y error de persistencia |

## Ubicación del código (fuera de esta carpeta)

| Ruta | Capa |
|---|---|
| `micro-his/expediente-medico/bootstrap.php`, `app.php` | Autocarga y raíz de composición |
| `micro-his/expediente-medico/src/Domain/` | Domain |
| `micro-his/expediente-medico/src/Application/` | Application (casos de uso y puertos) |
| `micro-his/expediente-medico/src/Persistence/` | Persistence (PDO, InMemory, registro de hospitales) |
| `micro-his/expediente-medico/src/Integration/` | Adaptadores a RBAC, Gobernanza y Pacientes |
| `micro-his/expediente-medico/src/Presentation/` | Presentation (Semana 4: MVC) |
| `micro-his/expediente-medico/database/schema.sql` | Base de prueba SQLite con los nombres reales |

## Cómo verificar

```bash
php micro-his/expediente-medico/tests/run.php
php micro-his/expediente-medico/tests/run.php | grep -E "C7|U-02|U-06|C6b|P-06|N-11"
```

## Estado

- **Implementado y probado:** capas, PDO preparado, camino feliz, reglas de dominio y errores de persistencia.
- **No verificado:** ejecución contra PostgreSQL (no hay servidor en el entorno; las pruebas PDO usan SQLite).
