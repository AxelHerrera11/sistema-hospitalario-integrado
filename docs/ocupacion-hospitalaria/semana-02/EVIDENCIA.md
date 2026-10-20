# Evidencia verificable - Semana 2

## Trazabilidad

| Evidencia | Verificación |
|---|---|
| Siete RF | Cubren consulta, regla temporal, clasificación, agregación, filtro, casos sin capacidad y falla. |
| Ocho RNF | Definen criterios medibles de seguridad, consistencia, integridad, privacidad, rendimiento, disponibilidad, accesibilidad e ISP. |
| Cinco escenarios | Comprueban camino feliz, límites temporales, discrepancia, tasa no aplicable y falla segura. |
| Diseño ISP | Muestra el contrato grande anterior y tres interfaces específicas por consumidor. |

## Checklist

- [x] Los RF se trazan a `UC-OCC-01` a `UC-OCC-06` de Semana 1.
- [x] La ocupación usa `started_at <= T` y (`ended_at IS NULL` o `ended_at > T`).
- [x] `beds.status` no se presenta como fuente autoritativa de ocupación.
- [x] Las inconsistencias permanecen visibles sin duplicar camas.
- [x] Los RNF contienen condiciones observables o umbrales medibles.
- [x] ISP se muestra antes y después con responsabilidades y dependencias.
- [x] La propuesta no agrega operaciones de escritura a ASII-09.
- [x] Se utiliza la fuente obligatoria del curso.

## Validaciones ejecutadas

```bash
npx -y @mermaid-js/mermaid-cli -i docs/ocupacion-hospitalaria/semana-02/fuentes/01-diseno-antes-isp.mmd -o /tmp/opencode/asii-09-antes.svg -b transparent
npx -y @mermaid-js/mermaid-cli -i docs/ocupacion-hospitalaria/semana-02/fuentes/02-diseno-despues-isp.mmd -o /tmp/opencode/asii-09-despues.svg -b transparent
xmllint --noout /tmp/opencode/asii-09-antes.svg /tmp/opencode/asii-09-despues.svg
git diff --check
npm run build
```

Resultados del 13 de octubre de 2026: Mermaid generó ambos diagramas sin errores, los dos SVG pasaron `xmllint`, `git diff --check` no reportó problemas y Vite compiló 92 módulos. `npm ci` reportó 11 vulnerabilidades heredadas del lockfile (1 baja, 8 altas y 2 críticas); esta entrega no modifica dependencias. El cambio documental suma 297 líneas, dentro del máximo de 400 para el próximo PR.

## Defensa breve

**¿Qué cambia con ISP?** Antes, todos los clientes dependían de siete operaciones, incluidas escrituras ajenas al dashboard. Después, cada cliente conoce un contrato de un método: resumen, fotografía de camas o catálogo de salas.

**¿Cómo se conserva una fotografía consistente?** `OccupancyAggregator` fija una sola fecha `T` y la entrega a `BedOccupancySnapshotReader`; el lector obtiene camas y estancias en una consulta única o una transacción de solo lectura con aislamiento repetible para evitar mezclar estados durante un traslado.

**¿Por qué no se corrige `beds.status`?** El dashboard es de consulta. Informa la discrepancia y deja la corrección al módulo propietario para no ocultar ni modificar datos operativos.
