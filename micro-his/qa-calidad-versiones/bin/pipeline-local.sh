#!/usr/bin/env bash
# Reproduce en local las etapas del workflow .github/workflows/qa-ci-e2e.yml y evalúa el
# quality gate con la misma política.
#
#   bash micro-his/qa-calidad-versiones/bin/pipeline-local.sh [carpeta-evidencias]
#
# Requisitos: PHP 8.2 con pdo_sqlite y pdo_pgsql, Node 22, composer install y npm ci ya hechos.
# Con PostgreSQL en 127.0.0.1:55431 y :55433 (usuario/contraseña postgres, como los servicios
# del workflow; ver docs/CI-E2E/guia-despliegue) también corren la suite Database y el E2E de
# pila completa. Sin PostgreSQL esas etapas no corren y el gate las informa como ausentes.
# QA_IMPORT_RELEASE=1.5.0-rc.1 importa el resultado a /calidad-versiones (qa:import-evidence).
set -u
cd "$(dirname "$0")/../../.."
EVIDENCE="${1:-qa-evidence}"
QA_BIN=micro-his/qa-calidad-versiones/bin
PORT="${QA_E2E_PORT:-8093}"
export APP_KEY="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=" CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync
rm -rf "$EVIDENCE" && mkdir -p "$EVIDENCE"/{phpunit,micro-his,frontend,contract,e2e}

step() { echo; echo "==> $1"; }
# Bases desechables del workflow: tests (*_test) o e2e (*_e2e), siempre en 55431/55433.
with_db() {
  CENTRAL_DB_HOST=127.0.0.1 CENTRAL_DB_PORT=55431 CENTRAL_DB_DATABASE="his_central_$1" CENTRAL_DB_USERNAME=postgres CENTRAL_DB_PASSWORD=postgres \
  HOSPITAL_DB_HOST=127.0.0.1 HOSPITAL_DB_PORT=55433 HOSPITAL_DB_DATABASE="his_hospital_$1" HOSPITAL_DB_USERNAME=postgres HOSPITAL_DB_PASSWORD=postgres \
  DB_CONNECTION=hospital "${@:2}"
}
migrate_all() {
  with_db "$1" php artisan migrate --force --database=central --path=database/migrations/central >/dev/null &&
  with_db "$1" php artisan migrate --force --database=central --path=database/migrations/framework >/dev/null &&
  with_db "$1" php artisan migrate --force --database=hospital --path=database/migrations/hospital >/dev/null
}

step "PostgreSQL de prueba (55431/55433)"
PG=0
if php "$QA_BIN/postgres-ci-local.php" check; then
  php "$QA_BIN/postgres-ci-local.php" ensure 55431 his_central_test &&
  php "$QA_BIN/postgres-ci-local.php" ensure 55433 his_hospital_test btree_gist &&
  migrate_all test && PG=1
fi
[ "$PG" = 1 ] && echo "Bases *_test listas: Feature, Database y E2E de pila completa usan PostgreSQL." \
  || echo "Sin PostgreSQL en 55431/55433: las pruebas que lo necesitan fallarán o quedarán ausentes."

step "Sintaxis PHP"
php "$QA_BIN/junit-command.php" --suite=sintaxis-php --junit="$EVIDENCE/syntax/php-lint.xml" --log="$EVIDENCE/syntax/php-lint.log" -- \
  bash -c 'fail=0; while IFS= read -r -d "" f; do out=$(php -l "$f" 2>&1) || { echo "$out"; fail=1; }; done < <(find app bootstrap config database routes tests micro-his -name "*.php" -print0); exit $fail'

step "Pint en archivos cambiados respecto de origin/develop (informativo)"
php "$QA_BIN/junit-command.php" --suite=pint-archivos-cambiados --junit="$EVIDENCE/lint/pint.xml" --log="$EVIDENCE/lint/pint.log" -- \
  php vendor/bin/pint --test --diff=origin/develop

step "Build del frontend"
php "$QA_BIN/junit-command.php" --suite=build-frontend --junit="$EVIDENCE/build/vite.xml" --log="$EVIDENCE/build/vite.log" -- bash -c 'npm run build'

step "PHPUnit Unit / Feature / Integration"
for suite in Unit Feature Integration; do
  lower=$(echo "$suite" | tr '[:upper:]' '[:lower:]')
  php vendor/phpunit/phpunit/phpunit --testsuite "$suite" --log-junit "$EVIDENCE/phpunit/$lower.xml" | tail -3
done

step "micro-his"
php "$QA_BIN/micro-his-suites.php" "$EVIDENCE/micro-his"

step "Frontend (node --test)"
mapfile -t files < <(find resources/js tests/Frontend micro-his -name '*.test.mjs' -o -name '*Test.mjs' | grep -v node_modules | sort)
node --test --test-reporter=spec --test-reporter-destination=stdout --test-reporter=junit --test-reporter-destination="$EVIDENCE/frontend/node.xml" "${files[@]}" | tail -8

step "Contrato de rutas"
php artisan route:list --json --path=api > "$EVIDENCE/contract/routes.json"
php "$QA_BIN/route-guard.php" "$EVIDENCE/contract/routes.json" config/rbac.php "$EVIDENCE/contract/route-guard.xml" | tail -3

if [ "$PG" = 1 ]; then
  step "Suite Database (PostgreSQL)"
  php vendor/phpunit/phpunit/phpunit --testsuite Database --log-junit "$EVIDENCE/phpunit/database.xml" | tail -3
fi

step "E2E del módulo QA en servidor aislado (puerto $PORT)"
db="$(mktemp -d)/qa-demo.sqlite"
QA_DEMO_DB="$db" php -S "127.0.0.1:$PORT" -t micro-his/qa-calidad-versiones/public micro-his/qa-calidad-versiones/public/index.php > "$EVIDENCE/e2e/qa-server.log" 2>&1 &
server=$!
trap 'kill $server ${api:-} 2>/dev/null' EXIT
QA_E2E_BASE="http://127.0.0.1:$PORT" node --test --test-reporter=spec --test-reporter-destination=stdout \
  --test-reporter=junit --test-reporter-destination="$EVIDENCE/e2e/qa-calidad.xml" tests/E2E/qa-calidad.e2e.mjs | tail -8

if [ "$PG" = 1 ]; then
  API_PORT="${E2E_API_PORT:-8100}"
  step "E2E de autenticación, tenant y RBAC (pila completa, puerto $API_PORT)"
  export APP_ENV=e2e APP_DEBUG=false TENANTS_REFRESH_COOKIE_SECURE=false \
    E2E_HOSPITAL_ID=e2e00000-0000-4000-8000-000000000001 E2E_HOSPITAL_2_ID=e2e00000-0000-4000-8000-000000000002 \
    E2E_ADMIN_EMAIL=admin.e2e@hospital-ficticio.test E2E_MEDICO_EMAIL=medico.e2e@hospital-ficticio.test
  # Secretos efímeros por ejecución, como en el workflow.
  JWT_SECRET="$(php -r 'echo base64_encode(random_bytes(48));')" E2E_PASSWORD="E2e-$(php -r 'echo bin2hex(random_bytes(12));')-Aa1!"
  export JWT_SECRET E2E_PASSWORD
  # Bases E2E nuevas en cada corrida, como los contenedores del job: la semilla no es repetible.
  php "$QA_BIN/postgres-ci-local.php" reset 55431 his_central_e2e && php "$QA_BIN/postgres-ci-local.php" reset 55433 his_hospital_e2e &&
  migrate_all e2e && with_db e2e env E2E_ALLOW_SEED=1 php tests/E2E/fixtures/seed-e2e.php
  with_db e2e php artisan serve --host=127.0.0.1 --port="$API_PORT" > "$EVIDENCE/e2e/laravel-server.log" 2>&1 &
  api=$!
  for _ in $(seq 1 20); do curl -s -o /dev/null "http://127.0.0.1:$API_PORT/up" && break; sleep 1; done
  E2E_API_BASE="http://127.0.0.1:$API_PORT" node --test --test-reporter=spec --test-reporter-destination=stdout \
    --test-reporter=junit --test-reporter-destination="$EVIDENCE/e2e/auth-rbac.xml" tests/E2E/auth-rbac.e2e.mjs | tail -8
  kill "$api" 2>/dev/null
  unset APP_ENV APP_DEBUG
fi

step "Quality gate"
php "$QA_BIN/quality-gate.php" --evidence-root="$EVIDENCE" --out-dir="$EVIDENCE/gate" \
  --sha="$(git rev-parse HEAD)" --release="0.0.0-local" --environment=local --db="$EVIDENCE/gate/qa-runs.sqlite"
code=$?
echo "quality-gate exit=$code (0 aprobado, 1 rechazado, 2 bloqueado, 3 configuración)"
if [ -n "${QA_IMPORT_RELEASE:-}" ]; then
  step "Importar a /calidad-versiones"
  php artisan qa:import-evidence "$EVIDENCE" --sha="$(git rev-parse HEAD)" --release="$QA_IMPORT_RELEASE" --environment=local
fi
exit $code
