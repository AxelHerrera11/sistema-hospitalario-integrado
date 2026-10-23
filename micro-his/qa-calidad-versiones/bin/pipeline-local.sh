#!/usr/bin/env bash
# Reproduce en local las etapas del workflow .github/workflows/qa-ci-e2e.yml que no
# necesitan PostgreSQL, y evalúa el quality gate con la misma política.
#
#   bash micro-his/qa-calidad-versiones/bin/pipeline-local.sh [carpeta-evidencias]
#
# Requisitos: PHP 8.2 con pdo_sqlite, Node 22, composer install y npm ci ya hechos.
# La suite Database y el E2E de pila completa NO corren aquí (necesitan las bases
# efímeras del job e2e): el gate los informa como ausentes, nunca como aprobados.
set -u
cd "$(dirname "$0")/../../.."
EVIDENCE="${1:-qa-evidence}"
QA_BIN=micro-his/qa-calidad-versiones/bin
PORT="${QA_E2E_PORT:-8093}"
export APP_KEY="base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=" CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync
rm -rf "$EVIDENCE" && mkdir -p "$EVIDENCE"/{phpunit,micro-his,frontend,contract,e2e}

step() { echo; echo "==> $1"; }

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

step "E2E del módulo QA en servidor aislado (puerto $PORT)"
db="$(mktemp -d)/qa-demo.sqlite"
QA_DEMO_DB="$db" php -S "127.0.0.1:$PORT" -t micro-his/qa-calidad-versiones/public micro-his/qa-calidad-versiones/public/index.php > "$EVIDENCE/e2e/qa-server.log" 2>&1 &
server=$!
trap 'kill $server 2>/dev/null' EXIT
QA_E2E_BASE="http://127.0.0.1:$PORT" node --test --test-reporter=spec --test-reporter-destination=stdout \
  --test-reporter=junit --test-reporter-destination="$EVIDENCE/e2e/qa-calidad.xml" tests/E2E/qa-calidad.e2e.mjs | tail -8

step "Quality gate"
php "$QA_BIN/quality-gate.php" --evidence-root="$EVIDENCE" --out-dir="$EVIDENCE/gate" \
  --sha="$(git rev-parse HEAD)" --release="0.0.0-local" --environment=local --db="$EVIDENCE/gate/qa-runs.sqlite"
code=$?
echo "quality-gate exit=$code (0 aprobado, 1 rechazado, 2 bloqueado, 3 configuración)"
exit $code
