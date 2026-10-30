# Diagramas: fuentes y regeneración de los SVG

Las fuentes editables están en `semana-XX/fuentes/*.mmd` (Mermaid). Cada SVG se guarda junto a su carpeta
`fuentes/` con el mismo nombre (`semana-01/fuentes/02-actividad.mmd` → `semana-01/02-actividad.svg`), con la
convención de los demás módulos. Los wireframes de la Semana 8 son SVG escritos a mano: el propio SVG es la fuente.

## Herramienta usada

En este entorno no hay `@mermaid-js/mermaid-cli` (`mmdc`) ni PlantUML. Los SVG se generan con **Mermaid 11.4.1**
(cargado desde `cdn.jsdelivr.net/npm/mermaid@11.4.1`) en un navegador, con el mismo procedimiento que
`docs/CI-E2E/diagramas`:

```bash
php -S 127.0.0.1:8097 docs/expedientes-medicos/diagramas/render-router.php
```

Después se abre `http://127.0.0.1:8097/`. La página `render.html` lista todas las fuentes `.mmd` del módulo, las
renderiza, cierra los `<br>` como `<br/>` para que el SVG sea XML válido, agrega fondo blanco y guarda cada SVG
junto a su fuente. El servidor solo acepta rutas `docs/expedientes-medicos/**/fuentes/*.mmd`.

## Alternativa con mermaid-cli

```bash
npx @mermaid-js/mermaid-cli -i docs/expedientes-medicos/semana-01/fuentes/02-actividad.mmd -o docs/expedientes-medicos/semana-01/02-actividad.svg -b white
```

## Regla

Cada vez que se edite un `.mmd`, se regenera su SVG y se revisa que siga coincidiendo con el código (nombres de
clase, métodos y códigos de error). La verificación de XML válido está en [`verificacion-svg.txt`](verificacion-svg.txt).
