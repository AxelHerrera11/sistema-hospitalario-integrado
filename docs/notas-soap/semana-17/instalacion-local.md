# Notas SOAP — Instalación local reproducible

Sin secretos en el repositorio: contraseñas y claves se generan en cada ejecución.

```bash
composer install --no-interaction
# Bases desechables (puertos libres, no los 55431/55433 de CI)
docker run -d --name notas-central  -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=his_central_e2e  -p 55442:5432 postgres:16
docker run -d --name notas-hospital -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=his_hospital_e2e -p 55443:5432 postgres:16

export APP_ENV=e2e E2E_ALLOW_SEED=1 APP_DEBUG=false
export CENTRAL_DB_HOST=127.0.0.1  CENTRAL_DB_PORT=55442  CENTRAL_DB_DATABASE=his_central_e2e  CENTRAL_DB_USERNAME=postgres CENTRAL_DB_PASSWORD=postgres
export HOSPITAL_DB_HOST=127.0.0.1 HOSPITAL_DB_PORT=55443 HOSPITAL_DB_DATABASE=his_hospital_e2e HOSPITAL_DB_USERNAME=postgres HOSPITAL_DB_PASSWORD=postgres
export JWT_SECRET="$(php -r 'echo bin2hex(random_bytes(32));')" APP_KEY="$(php artisan key:generate --show)"

php -d extension=pdo_pgsql artisan migrate --database=central  --path=database/migrations/central  --realpath --force
php -d extension=pdo_pgsql artisan migrate --database=hospital --path=database/migrations/hospital --realpath --force

export E2E_PASSWORD="$(php -r 'echo bin2hex(random_bytes(12));')" E2E_HOSPITAL_ID=aaaa1111-1111-4111-8111-111111111111 \
       E2E_HOSPITAL_2_ID=bbbb2222-2222-4222-8222-222222222222 E2E_ADMIN_EMAIL=admin@e2e.test E2E_MEDICO_EMAIL=dra.lopez@e2e.test
php -d extension=pdo_pgsql tests/E2E/fixtures/seed-e2e.php
```

Después hay que sembrar en HOSPITAL el usuario local de la médica (`local_user_refs.central_user_id` = su `user_id` de CENTRAL), su perfil médico, un paciente, un episodio con asignación activa y su expediente abierto; los datos están en `tests/Database/Hospital/SeedsSoapCare.php`. Con eso:

```bash
php -d extension=pdo_pgsql -S 127.0.0.1:8099 -t public &
# login → access_token, y luego las rutas de [evidencia-funcional.md](./evidencia-funcional.md)
```

Pruebas automáticas (sin servidor): `HOSPITAL_DB_PORT=55443 php -d extension=pdo_pgsql vendor/bin/phpunit tests/Database/Hospital/NotasSoap*`, `tests/Feature/NotasSoap` y las suites de `micro-his/notas-soap/tests/`.
