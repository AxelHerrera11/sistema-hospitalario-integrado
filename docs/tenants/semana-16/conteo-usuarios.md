# Semana 16 — Conteo de usuarios del hospital (parte 8)

## Hallazgo

Al levantar la aplicación con datos de demo, asignar un segundo rol a una persona subió «Usuarios con acceso»
de 8 a 9 en `/admin/users`, aunque seguían siendo 8 personas. `ListHospitalUsers` unía `central_users` con
`hospital_memberships`: cada rol era una fila y `paginate()` cuenta filas aunque la consulta use `distinct()`.
La lista se veía bien, pero `total` y `last_page` salían inflados.

## Corrección

El filtro de membresía vigente pasa a `whereExists`: cada usuario es una sola fila y el conteo es exacto. Las
reglas no cambian (hospital de la sesión, membresía activa, `valid_from` y `valid_to`, búsqueda y estado).

## Validación

```bash
php vendor/bin/phpunit tests/Feature/Tenants
# OK (66 tests, 425 assertions)
```

`test_user_with_several_roles_is_counted_once_in_the_listing` fallaba antes del cambio (`total` 3 en lugar de 2).
