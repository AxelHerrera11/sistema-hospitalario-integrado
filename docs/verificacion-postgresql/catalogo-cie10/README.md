# Catálogo CIE-10 (`icd10_codes`)

## Propiedad

- **Esquema y migración:** ASII-09 (verificación de base de datos).
- **Consumo y significado clínico:** ASII-11 (Notas SOAP y diagnósticos), a través del puerto `Icd10Catalog`.
- La tabla está **fuera de las 27 entidades** de la especificación. El grupo aprobó incorporarla como entidad local de catálogo el 30/10/2026; el inventario y el contrato de Notas SOAP deben reflejarlo.

## Contrato de la tabla

| Columna | Tipo | Regla |
|---|---|---|
| `code` | `varchar(10)` | PK. Mayúsculas, sin espacios, formato CIE-10 con punto opcional (`J18.9`, `I10`, `S72.001A`). |
| `description` | `text` | Obligatoria, recortada y no vacía. |
| `active` | `boolean` | `NOT NULL DEFAULT true`. Nunca entero. Un código retirado se desactiva, no se borra. |

No hay FK desde `diagnoses`: esa tabla guarda su propio `code` y `description` como snapshot para que el histórico clínico no dependa de cambios posteriores del catálogo. La validación de vigencia se hace en la aplicación antes de insertar el diagnóstico.

## Fuente oficial

- **OMS, CIE-10, versión 2019:** <https://icd.who.int/browse10/2019/en>.
- Adopción regional de referencia: **PAHO/OMS, Clasificación Internacional de Enfermedades, 10.ª revisión**.
- El catálogo completo no se versiona en este repositorio: son miles de filas, no cabe en el límite de 400 líneas por PR y la OMS tiene condiciones de uso. Se descarga del sitio oficial y se carga con el importador/seed en un PR aparte.

## Carga por seed

Formato acordado para el archivo externo:

```csv
code,description,active
J18.9,"Neumonía, no especificada",true
I10,Hipertensión esencial,true
```

- Codificación UTF-8, encabezado obligatorio, coma como separador y comillas para descripciones con coma.
- El seed debe ser idempotente: `upsert` por `code`, actualizando `description` y `active`.
- `active = false` para códigos retirados; nunca `DELETE`.
- El seed mínimo de 20 códigos que usa Notas SOAP para pruebas vive en `#1110`; no se duplica aquí.
- La carga del archivo completo queda para el PR del importador, con su prueba y sin comprometer el catálogo oficial dentro del repositorio.

## Fuera de alcance

- No resuelve R-06 (varias notas por expediente). Ese cambio requiere agrupar las versiones por una serie de nota (`note_series_id`) y modificar la unicidad y el trigger de `clinical_notes` en una migración separada.
- No agrega FK ni cambia `diagnoses`.
