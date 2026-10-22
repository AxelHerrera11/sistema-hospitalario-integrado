# Mapa de navegación

Las rutas están bajo el guard de sesión existente. El flujo de demostración
requiere `?demo=1`, usa solo datos locales ficticios y nunca llama a la API.

```mermaid
flowchart TD
  Entry["/patients"] --> Gate{"Permisos efectivos disponibles?"}
  Gate -- "No / sin demo" --> Restricted["Acceso restringido<br/>sin datos"]
  Restricted --> Demo["Prototipo local explícito"]
  Demo --> Search["Buscar código ficticio"]
  Search -- "DEMO-001" --> Detail["Detalle de demostración"]
  Search -- "Otro / vacío" --> Recover["Sin resultado / corregir código"]
  Recover --> Search
  Detail --> Edit["/patients/edit"]
  Edit --> Validate{"Validación local"}
  Validate -- "Error" --> Edit
  Validate -- "Correcto" --> Saved["/patients/saved"]
  Demo --> Register["/patients/new"]
  Register --> Validate
  Detail --> Restricted
```

El estado restringido no revela registro ni permite acciones. El enlace a
demo deja claro que es una excepción para explorar solo pantallas con datos
ficticios, no una autorización efectiva.
