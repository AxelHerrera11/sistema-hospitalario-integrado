# Guion de defensa y demo funcional

Material para la evaluación final. El guion se apoya en evidencia ejecutada; nada de lo que se
demuestra depende de una afirmación sin respaldo.

## Apertura (2 minutos)

«Entrego un módulo de **consulta agregada** de la ocupación hospitalaria por sala y estado de
cama. La decisión de diseño que sostiene todo lo demás es una sola: **la ocupación se deriva del
intervalo de la estancia, no del estado operativo de la cama**. Todo lo que el módulo informa sale
de esa regla, y todo lo que no cumple la regla se muestra como inconsistencia en lugar de
ocultarse o corregirse.»

## Demo funcional (5 minutos)

### Paso 1 - El dominio sin base de datos

```bash
php micro-his/ocupacion-hospitalaria/tests/run.php
```

Señalar las tres familias de verificaciones: camino feliz y reglas de dominio, error de
persistencia (`503`, nunca ocupación cero) y arquitectura (Domain y Application sin PDO,
Presentation sin SQL, Persistence con sentencias preparadas).

### Paso 2 - El contrato HTTP contra PostgreSQL real

```bash
php artisan test tests/Feature/Ocupacion tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
```

Antes de ejecutar, mostrar el código de la regla que se está probando:
`PdoBedSnapshotReader::readAt()` con la expresión `EXISTS` sobre `bed_stays` y
`OccupancyCalculator` con la comparación entre estancia y estado operativo.

### Paso 3 - La pantalla

Abrir `/ocupacion` y recorrer: estado de carga, fotografía con su corte y antigüedad, actualización,
tabla por sala y aviso de inconsistencias. El filtro se muestra únicamente en el prototipo de la
semana 11 porque todavía no existe en la pantalla productiva.

### Paso 4 - La evidencia de despliegue

Abrir `docs/ocupacion-hospitalaria/semana-17/bitacora-despliegue.md`: login `200`, ocupación `200`
con datos reales, `401` sin token, `403` por tenant, `403` por permiso y `503` con la base caída,
todo con `APP_DEBUG=false` y sin traza en la respuesta.

## Cambio solicitado que se defiende en vivo

**Solicitud:** «Si una cama tiene estancia vigente y `beds.status` dice `available`, ¿qué muestra
el dashboard y qué hace con el dato?»

**Respuesta:**

1. La cama se clasifica **ocupada**, porque la ocupación clínica la define la estancia.
2. Se reporta una **inconsistencia** entre la estancia vigente y el estado operativo: el módulo no
   la oculta ni la corrige.
3. El módulo **no libera** la cama ni modifica `beds.status`: esos datos son propiedad de
   ASII-06/07/08.
4. Con la misma lógica, una cama con `beds.status = occupied` **sin** estancia no cuenta como
   ocupada: queda como «no verificada» y alimenta el contador de inconsistencias.

**Evidencia que se muestra en el momento:**

```bash
php artisan test --filter=test_operational_status_does_not_change_the_interval_derived_occupancy
```

**Límite reconocido:** cambiar la regla exige revisar el contrato v1 y migrar la clasificación; el
plan transversal deja esa decisión al equipo.

## Preguntas esperadas y respuesta breve

| Pregunta | Respuesta |
|---|---|
| ¿Por qué no usar `beds.status` como ocupación? | Porque es estado operativo y puede discrepar del hecho clínico; la estancia es la fuente autoritativa. |
| ¿Por qué un puerto y no una consulta directa? | Para probar el mismo caso de uso con y sin base de datos, y para que el controlador no dependa del esquema. |
| ¿Por qué no calcular la ocupación en Vue? | Duplicaría el dominio y la respuesta podría divergir de la autoritativa. |
| ¿Por qué se conserva la fotografía anterior ante una falla? | Para poder operar, pero solo si se rotula con el corte y la antigüedad; nunca se presenta como actual. |
| ¿Qué falta ante un `403` posterior a una consulta exitosa? | El backend no entrega cifras, pero el cliente Vue todavía debe limpiar el `snapshot` previo en memoria. |
| ¿El módulo crea tablas? | No. Lee `wards`, `beds` y `bed_stays`, propiedad de ASII-06/07/08. |
| ¿Cuándo tendría sentido un microservicio? | Solo con métricas medidas de carga, p95 y disponibilidad que justifiquen autonomía y costo de red. |
| ¿Qué quedó sin probar? | Contraste y reflow con navegador real, lector de pantalla, volumen de 10 000 camas y el filtro por sala, que aún no existe. |

## Cierre (1 minuto)

«El módulo entrega consulta, no acción: informa ocupación y hace visibles las discrepancias, sin
convertirse en dueño de camas. La evidencia está en el repositorio, con la salida de los comandos
que sostienen cada afirmación, y los límites están declarados en vez de esconderse.»

## Checklist previo a la defensa

- [ ] Contenedores `bd-central` y `bd-hospital` levantados y migraciones de `develop` aplicadas.
- [ ] `vendor/` instalado y `.env` de pruebas con `APP_KEY` y puertos 55431 / 55433.
- [ ] Los cuatro comandos de la demo ejecutados una vez, sin fallos, con su salida a la vista.
- [ ] Prototipo servido en `php -S` para el paso 3.
- [ ] Capturas de la semana 11 abiertas como respaldo si el navegador falla.
- [ ] Este guion y la matriz de trazabilidad de la semana 18 abiertos.
