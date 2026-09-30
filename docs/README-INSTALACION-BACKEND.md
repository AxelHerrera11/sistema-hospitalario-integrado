# Instalación del backend (terminal)

Guía para instalar **solo lo necesario** y ejecutar el **API Laravel** con `php artisan serve`. El frontend (Vite) es opcional; si lo usas, configura `VITE_API_URL` en `.env` para que apunte al mismo origen del backend.

La visión general del proyecto está en [README.md](../README.md).

---

## Instrucciones para agentes de IA (y humanos)

**Objetivo:** dejar el backend ejecutable desde la **raíz del repositorio** (`sistema-hospitalario-integrado-SistenasII-2026`). No hay carpeta `backend` aparte: el proyecto Laravel está en la raíz (`artisan`, `composer.json`, `app/`, `routes/`).

**Stack relevante:** Laravel 12, PostgreSQL 13+ (ver README para crear la base de datos), JWT (`tymon/jwt-auth`), Spatie Permission y multitenancy propio por cabecera `X-Tenant-ID` (trait `App\Models\Concerns\BelongsToTenant`). Ya no se usa Stancl Tenancy.

**Antes de instalar, comprobar en terminal:**

| Comando | Criterio de éxito |
|---------|-------------------|
| `php -v` | PHP ≥ 8.2 |
| `composer -V` | Composer 2.x |

**Reglas de shell:**

- En **Windows PowerShell**, encadenar con `;`, no con `&&` (versiones antiguas no lo soportan).
- Copiar `.env`: en PowerShell `Copy-Item .env.example .env`; en bash `cp .env.example .env`.

**Orden obligatorio de pasos:** dependencias Composer → archivo `.env` → archivo SQLite vacío → `key:generate` → `jwt:secret` → `migrate --seed` → `serve` (o verificación con `route:list`).

**Criterios de éxito al terminar:**

- Existe `vendor/autoload.php`.
- Existe `database/database.sqlite` (si usas SQLite).
- `php artisan migrate --seed` termina sin error (incluye `RoleSeeder` y `TenantSeeder`).
- `php artisan route:list --path=api` lista rutas bajo `api/v1`.

**Usuarios demo** (contraseña `password`): `admin+san-marcos-demo@demo.local`, `recep+san-marcos-demo@demo.local`, `lab+san-marcos-demo@demo.local`, `doctor1+san-marcos-demo@demo.local` … `doctor5+…`. Para el segundo hospital cambia `san-marcos-demo` por `santa-elena-demo` y usa el tenant `00000000-0000-4000-8000-000000000002`.

---

## Requisitos

| Componente | Versión / notas |
|------------|-----------------|
| **PHP** | ≥ 8.2 (`composer.json` declara `^8.2`). |
| **Composer** | ≥ 2.x |
| **Base de datos** | **SQLite** (recomendado para empezar) o MySQL 8. |

### Extensiones PHP necesarias

Para `composer install`, migraciones y JWT:

- `openssl`, `pdo`, **`pdo_pgsql` y `pgsql` (PostgreSQL)**, `pdo_sqlite` (solo si usas SQLite), `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `bcmath`

En Windows, si instalas PHP con **winget** y no existe `php.ini`, copia `php.ini-development` a `php.ini` en la carpeta de PHP y descomenta `extension_dir = "ext"` y las líneas `extension=openssl`, `extension=mbstring`, `extension=pdo_pgsql`, `extension=pgsql`, etc.

---

## 1. Instalar PHP y Composer

### Windows

1. **PHP** (ejemplo con winget):

   ```powershell
   winget install PHP.PHP.8.3 --accept-package-agreements
   ```

   Cierra y vuelve a abrir la terminal (o el IDE) para actualizar el `PATH`. Si `php` no se reconoce:

   ```powershell
   $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
   ```

2. **Composer:** [getcomposer.org](https://getcomposer.org/download/). Verifica con `composer -V`.

### Linux / macOS

Instala PHP 8.2+ y extensiones con el gestor de tu distro o Homebrew; luego Composer según la documentación oficial.

---

## 2. Ir al directorio del proyecto

```bash
cd /ruta/al/sistema-hospitalario-integrado-SistenasII-2026
```

---

## 3. Dependencias PHP

```bash
composer install
```

`composer install` puede tardar varios minutos; al final debe existir `vendor/autoload.php`. Si hay error de **OpenSSL** o TLS, activa `extension=openssl` en `php.ini` (`php --ini` para ver qué archivo carga).

---

## 4. Entorno (`.env`)

**Bash:**

```bash
cp .env.example .env
```

**PowerShell:**

```powershell
Copy-Item .env.example .env
```

El archivo `.env.example` incluye `APP_KEY=` vacío y valores por defecto para PostgreSQL (`DB_CONNECTION=pgsql`, base `hospital_his`, usuario `his`). Es importante que exista la línea `APP_KEY=` antes de ejecutar `key:generate`.

### PostgreSQL (por defecto)

1. Crear usuario y bases (en `psql` o pgAdmin, como superusuario):

   ```sql
   CREATE USER his WITH PASSWORD 'his123';
   CREATE DATABASE hospital_his OWNER his;
   CREATE DATABASE hospital_his_test OWNER his;
   ```

2. Verificar que PHP tenga el driver: `php -m` debe listar `pdo_pgsql`. En Windows,
   descomentar `extension=pdo_pgsql` y `extension=pgsql` en `php.ini`.

3. Si usas otros datos de conexión, ajusta `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
   `DB_USERNAME` y `DB_PASSWORD` en `.env` (y en `phpunit.pgsql.xml` para pruebas).

### SQLite (alternativa sin instalar nada)

En `.env` cambia a `DB_CONNECTION=sqlite`, elimina las demás líneas `DB_*` y crea el archivo:

```bash
touch database/database.sqlite
```

Útil para probar rápido, pero antes de integrar valida siempre con PostgreSQL.

### Claves

```bash
php artisan key:generate
php artisan jwt:secret
```

En entornos no interactivos puedes usar `--force` si el comando lo permite.

---

## 5. Base de datos y datos iniciales

```bash
php artisan migrate --seed
```

Esto crea tablas (usuarios, tenants, permisos, caché en BD, colas, etc.) y ejecuta **RoleSeeder** y **TenantSeeder** (tenant demo).

---

## 6. Levantar el backend

```bash
php artisan serve
```

Por defecto: **http://127.0.0.1:8000**

- Base del API: **http://127.0.0.1:8000/api/v1**
- Las peticiones al API requieren la cabecera **`X-Tenant-ID`** (UUID del tenant). Tras el seed, ejemplo: **`X-Tenant-ID: 00000000-0000-4000-8000-000000000001`** (ver [README.md](../README.md)).

Útil en red local:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Tras cambiar `.env`:

```bash
php artisan config:clear
```

---

## 7. Comprobar que el backend está bien configurado

```bash
php artisan route:list --path=api
```

Deberías ver rutas como `api/v1/auth/login`, `register`, `me`, etc.

Para pruebas HTTP usa Thunder Client, Postman o `curl` con la cabecera `X-Tenant-ID` anterior.

---

## Problemas frecuentes

| Síntoma | Acción |
|---------|--------|
| `php` no se reconoce (Windows) | Reinicia terminal o IDE; refresca `PATH` (sección 1). Revisa entradas rotas en PATH (p. ej. XAMPP inexistente). |
| Composer / TLS y OpenSSL | `extension=openssl` en `php.ini`; `php --ini`. |
| `could not find driver` | Falta `pdo_pgsql` en `php.ini`. |
| `password authentication failed` / `database does not exist` | Revisar `DB_*` en `.env` y que exista la BD `hospital_his`. |
| Error al migrar SQLite | Archivo `database/database.sqlite` existente; `pdo_sqlite` activo. |
| `Unable to set application key` | Asegura que `.env` contiene una línea `APP_KEY=` (vacía o no); copia de nuevo desde `.env.example` si hace falta y repite `php artisan key:generate`. |
| `composer install` parece colgado en “Generating optimized autoload files” | Esperar (puede tardar en Windows con antivirus); al terminar debe aparecer `package:discover`. Si tras muchos minutos no avanza, revisar antivirus/exclusiones o ejecutar de nuevo `composer install`. |

---

## Resumen de comandos (orden)

Desde la **raíz** del repositorio.

**Bash:**

```bash
composer install
cp .env.example .env
# PostgreSQL: crear antes la BD hospital_his (sección 4). Solo SQLite: touch database/database.sqlite
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

**PowerShell (ejemplo en una sesión):**

```powershell
Set-Location "ruta\completa\al\sistema-hospitalario-integrado-SistenasII-2026"
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
# (Solo SQLite) New-Item -ItemType File -Path database\database.sqlite -Force
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

### Frontend Vite (opcional)

Si corres `npm run dev` en paralelo, alinea en `.env`:

- `APP_URL=http://127.0.0.1:8000` (o el host/puerto que use `php artisan serve`)
- `VITE_API_URL=http://127.0.0.1:8000/api/v1`

Usa el mismo host y puerto que muestre la consola de `php artisan serve`.
