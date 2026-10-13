# Evidencia técnica y Git - Semana 1

## Trazabilidad remota

- Repositorio: https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3
- Issue principal: https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36
- Rama: `feature/asii-09-semana-01-dashboard-ocupacion-javifa2324`
- Árbol de archivos: https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/tree/feature/asii-09-semana-01-dashboard-ocupacion-javifa2324/docs/ocupacion-hospitalaria/semana-01
- Commit documental evaluado: [`659719f`](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/commit/659719f).
- Pull Request: [#45](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/45), abierto hacia `develop` con `Refs #36`.

## Validaciones ejecutadas

| Validación | Resultado |
|---|---|
| Render de casos de uso con PlantUML | Exitoso. |
| Render de actividad con PlantUML | Exitoso. |
| Render de secuencia con Mermaid CLI | Exitoso. |
| Validación XML de los tres SVG con `xmllint --noout` | Exitosa, sin errores. |
| `git diff --check` | Exitoso, sin errores. |
| `npm ci` | Exitoso; reportó vulnerabilidades de dependencias ya fijadas por el repositorio base. |
| `npm run build` | Exitoso; 92 módulos transformados. |

## Limitaciones del entorno

Las pruebas PostgreSQL no se ejecutan en este entorno porque PHP no tiene cargadas las extensiones `pdo_pgsql` y `pgsql`. La entrega solo incorpora documentación y diagramas; no modifica migraciones, API ni código ejecutable.
