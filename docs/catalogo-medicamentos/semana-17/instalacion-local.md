# Instalación local reproducible del catálogo

Entorno aislado de verificación. **No es el despliegue final:** no hay staging ni on-premise autorizados. No use bases compartidas ni datos clínicos reales; las credenciales viven solo en el entorno del proceso o en `.env`, que Git ignora.

1. **Dependencias:** `composer install`, `npm ci` y `cp .env.example .env && php artisan key:generate`. En OneDrive, ejecute `attrib -R bootstrap\cache /S /D` y `attrib -R storage /S /D`.
2. **PostgreSQL desechable** (15 o superior, con `btree_gist`): cree dos clústeres o dos bases propias, CENTRAL y HOSPITAL. En la base HOSPITAL ejecute `CREATE EXTENSION btree_gist;`.
3. **Variables del proceso**, sin guardarlas en el repositorio: `HOSPITAL_DB_TENANT_ID` con el UUID del hospital dueño de la base (sin él, el catálogo responde 503), y `CENTRAL_DB_*` y `HOSPITAL_DB_*` apuntando a esas bases, `JWT_SECRET` aleatorio (por ejemplo `php -r "echo bin2hex(random_bytes(32));"`) y `APP_DEBUG=false`.
4. **Migraciones:**

   ```bash
   php artisan migrate --database=central  --path=database/migrations/central  --realpath --force
   php artisan migrate --database=central  --path=database/migrations/framework --realpath --force
   php artisan migrate --database=hospital --path=database/migrations/hospital --realpath --force   # incluye 000500 medications
   ```

5. **Usuarios ficticios:** en CENTRAL, una red, un hospital, `central_users`, `auth_credentials` (hash con `password_hash`) y `hospital_memberships` con los roles `admin_hospital`, `enfermeria` y `recepcion`. `php artisan auth:provision-first-superadmin` crea la cuenta inicial de forma interactiva.
6. **Interfaz:** `VITE_API_URL` tiene que coincidir con el origen que sirve la aplicación. Con `php artisan serve` en otro puerto, compile con la ruta relativa:

   ```bash
   VITE_API_URL=/api/v1 npm run build    # en Git Bash, anteponga MSYS_NO_PATHCONV=1: si no, convierte /api/v1 en una ruta de Windows
   php artisan serve --host=127.0.0.1 --port=8770
   ```

7. **Comprobación:** abra `/login` con el UUID del hospital y el correo ficticio, y luego entre en «Medicamentos». Para el recorrido automatizado, instale `npm install puppeteer-core@23.11.1` en una carpeta aparte, copie ahí `evidencia/catalogo-e2e.mjs` y `fallo-e2e.mjs`, y ejecute `node catalogo-e2e.mjs http://127.0.0.1:8770 <uuid-hospital>`.

**Pendiente para el despliegue final:** un entorno staging u on-premise autorizado con PostgreSQL CENTRAL y HOSPITAL, secretos gestionados fuera del repositorio, la ruta de la base HOSPITAL por hospital y la repetición de los pasos 4 a 7 en ese entorno.
