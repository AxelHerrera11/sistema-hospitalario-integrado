#!/usr/bin/env bash
# Recorrido HTTP real contra la API v1 de Laravel (Semana 16, ASII-17): login JWT, tenant, RBAC, guard de #1154 y PostgreSQL (lab_tests 000600).
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-catalogo.sh <base> <hospital-id> <otro-hospital-id>
# Imprime «esperado / obtenido» por paso; nunca imprime tokens. Usuarios ficticios: superadmin, técnico de laboratorio y recepción.
B=$1; H=$2; OTRO=$3; J='Content-Type: application/json'; HOY=$(date +%F)
token() { curl -s -H "X-Tenant-ID: $H" -H "$J" -d "{\"email\":\"$1\",\"password\":\"$E2E_PASSWORD\"}" "$B/api/v1/auth/login" \
  | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo $j["access_token"] ?? $j["data"]["access_token"] ?? "";'; }
paso() { # paso <descripción> <esperado> <curl args...>
  local d=$1 esperado=$2; shift 2
  local cuerpo obtenido; cuerpo=$(curl -s -w '\n%{http_code}' "$@"); obtenido=${cuerpo##*$'\n'}; ULTIMO=${cuerpo%$'\n'*}
  printf '%s  %-66s esperado %s, obtenido %s\n' "$([ "$obtenido" = "$esperado" ] && echo PASS || echo FAIL)" "$d" "$esperado" "$obtenido"
}
SUP=$(token superadmin.e2e@hospital-ficticio.test); TEC=$(token tecnico.e2e@hospital-ficticio.test); REC=$(token recepcion.e2e@hospital-ficticio.test)
[ -n "$SUP" ] && [ -n "$TEC" ] && [ -n "$REC" ] || { echo "FAIL  login de superadmin, técnico o recepción"; exit 1; }
URL="$B/api/v1/lab-tests"
GLU="{\"code\":\"glu\",\"name\":\"Glucosa\",\"specimen_type\":\"serum\",\"min_volume_ml\":0.5,\"result_type\":\"numeric\",\"unit\":\"mg/dL\",\"reference_low\":70,\"reference_high\":100,\"critical_low\":40,\"critical_high\":400,\"valid_from\":\"$HOY\"}"
paso 'sin token → 401' 401 -H "X-Tenant-ID: $H" "$URL"
paso 'token del hospital A con X-Tenant-ID del B → 403' 403 -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $TEC" "$URL"
paso 'recepción sin laboratorio.catalogo.ver → 403 RBAC' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" "$URL"
paso 'técnico intenta registrar (sin administrar) → 403' 403 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $TEC" -H "$J" -d "$GLU" "$URL"
paso 'superadmin registra glucosa (código «glu») → 201' 201 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $SUP" -H "$J" -d "$GLU" "$URL"
echo "      código guardado: $(php -r 'echo json_decode($argv[1], true)["data"]["code"] ?? "?";' "$ULTIMO"), versión $(php -r 'echo json_decode($argv[1], true)["data"]["version_no"] ?? "?";' "$ULTIMO")"
paso 'otra versión vigente del mismo código → 409' 409 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $SUP" -H "$J" -d "$GLU" "$URL"
paso 'rangos incoherentes (crítico bajo 80) → 422' 422 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $SUP" -H "$J" -d "${GLU/\"critical_low\":40/\"critical_low\":80}" "$URL"
paso 'técnico busca «gluc» vigente hoy → 200' 200 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $TEC" "$URL?search=gluc"
echo "      pruebas encontradas: $(php -r 'echo count(json_decode($argv[1], true)["data"]["lab_tests"]);' "$ULTIMO")"
paso 'técnico consulta con fecha inválida → 422' 422 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $TEC" "$URL?date=2026-02-30"
