# Checklist de calidad de datos con resultados medidos (Semana 9)

## 3. Checklist de calidad de datos con resultados medidos

Consultas ejecutadas sobre la base HOSPITAL de prueba con el seeder aplicado.

| # | Criterio | Consulta | Resultado | Veredicto |
|---:|---|---|---|---|
| 1 | Toda cama pertenece a una sala existente | Camas sin sala válida | 0 | Cumple |
| 2 | No hay códigos de cama duplicados en una sala | Grupos con código repetido | 0 | Cumple |
| 3 | Toda estancia cae dentro del periodo de su episodio | Estancias fuera de periodo | 0 | Cumple |
| 4 | No hay episodios con dos estancias abiertas | Episodios con más de una estancia abierta | 0 | Cumple |
| 5 | Los episodios cubren los tres estados | `open` 1, `closed` 1, `cancelled` 1 | 3 estados | Cumple |
| 6 | Las camas cubren estados operativo y de servicio | `available` 2, `maintenance` 1 | 2 estados | Cumple |
| 7 | La ocupación es calculable en un corte | Corte 2026-10-19 12:00 UTC: `U1-01` ocupada, `U1-02` en mantenimiento, `UC1-01` libre | 1 de 3 | Cumple |
| 8 | Ningún dato de contacto fuera del dominio de prueba | Contactos distintos de `@example.test` | 0 | Cumple |
| 9 | Todo episodio abierto tiene al menos una estancia | Episodios `open` sin estancia | 0 | Cumple |
| 10 | Los identificadores son deterministas | UUID fijos en el script | 100 % fijos | Cumple |

### 3.1 Observación sobre el criterio 9

La primera medición de "episodios sin estancia" devolvió 1: el episodio `cancelled`,
que por definición no tiene estancia porque nunca se admitió. El criterio se corrigió
a "todo episodio **abierto** tiene al menos una estancia" y el resultado es 0. Un
criterio mal redactado produce ruido; el checklist se documenta con la corrección.
