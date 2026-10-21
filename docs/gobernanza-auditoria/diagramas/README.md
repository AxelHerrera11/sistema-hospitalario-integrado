# Diagramas: fuentes y regeneración de los SVG

Las fuentes editables están en `semana-XX/fuentes/*.mmd` (Mermaid). Cada SVG se guarda junto a su carpeta `fuentes/`, con el mismo nombre (`semana-01/fuentes/02-actividad.mmd` → `semana-01/02-actividad.svg`), siguiendo la convención de los demás módulos (`docs/rbac`, `docs/alergias`…).

## Herramienta usada

En este entorno no había `@mermaid-js/mermaid-cli` (`mmdc`) ni PlantUML. Los SVG se generaron con **Mermaid 11.4.1** (cargado desde `cdn.jsdelivr.net/npm/mermaid@11.4.1`) en el navegador integrado de Claude Code, el 2026-10-20, con este procedimiento:

```bash
php -S 127.0.0.1:8092 docs/gobernanza-auditoria/diagramas/render-router.php
```

Después hay que abrir `http://127.0.0.1:8092/`. La página `render.html`:

1. lista todas las fuentes `.mmd` del módulo;
2. las renderiza;
3. cierra los `<br>` como `<br/>` para que el SVG sea XML válido;
4. agrega el fondo blanco;
5. guarda cada SVG junto a su fuente.

El servidor (`render-router.php`) solo acepta rutas `docs/gobernanza-auditoria/**/fuentes/*.mmd`.

**Verificación hecha:** los 13 SVG cargan como XML válido (`simplexml_load_file`). `01-casos-de-uso.svg` se abrió como archivo `image/svg+xml` independiente y se ve correctamente.

## Alternativa con mermaid-cli

Si `mmdc` está instalado:

```bash
npx @mermaid-js/mermaid-cli -i docs/gobernanza-auditoria/semana-01/fuentes/02-actividad.mmd -o docs/gobernanza-auditoria/semana-01/02-actividad.svg -b white
```

## Regla

Cada vez que se edite un `.mmd`, hay que regenerar su SVG y revisar que siga coincidiendo con el código (nombres de clase y códigos de error).
