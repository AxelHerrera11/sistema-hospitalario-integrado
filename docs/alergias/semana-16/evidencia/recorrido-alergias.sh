#!/usr/bin/env bash
# Recorrido HTTP real contra la API v1 de Laravel (Semana 16, ASII-12): login JWT, tenant, RBAC, guard de #1154 y PostgreSQL.
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-alergias.sh <base> <hospital-id> <otro-hospital-id> <paciente-uuid>
# Imprime «esperado / obtenido» por paso; nunca imprime tokens. Datos ficticios del seed de E2E.
B=$1; H=$2; OTRO=$3; P=$4; J='Content-Type: application/json'
token() { curl -s -H "X-Tenant-ID: $H" -H "$J" -d "{\"email\":\"$1\",\"password\":\"$E2E_PASSWORD\"}" "$B/api/v1/auth/login" \
  | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo $j["access_token"] ?? $j["data"]["access_token"] ?? "";'; }
paso() { # paso <descripción> <esperado> <curl args...>
  local d=$1 esperado=$2; shift 2
  local cuerpo obtenido; cuerpo=$(curl -s -w '\n%{http_code}' "$@"); obtenido=${cuerpo##*$'\n'}; ULTIMO=${cuerpo%$'\n'*}
  printf '%s  %-66s esperado %s, obtenido %s\n' "$([ "$obtenido" = "$esperado" ] && echo PASS || echo FAIL)" "$d" "$esperado" "$obtenido"
}
MED=$(token "$E2E_MEDICO_EMAIL"); ADM=$(token "$E2E_ADMIN_EMAIL")
[ -n "$MED" ] && [ -n "$ADM" ] || { echo "FAIL  login de médico o administrador"; exit 1; }
ALERTA="$B/api/v1/patients/$P/allergy-alert"; ALTA="$B/api/v1/patients/$P/allergies"
PENI='{"allergen_code":"PENICILINA","allergen_name":"Penicilina","reaction":"Anafilaxia","allergy_type":"allergy","severity":"life_threatening"}'
paso 'sin token → 401' 401 -H "X-Tenant-ID: $H" "$ALERTA"
paso 'token del hospital A con X-Tenant-ID del B → 403' 403 -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $MED" "$ALERTA"
paso 'admin_hospital sin alergias.ver → 403 RBAC' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $ADM" "$ALERTA"
paso 'médico consulta un paciente sin alergias → 200 (none)' 200 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$ALERTA"
echo "      nivel: $(php -r 'echo json_decode($argv[1], true)["data"]["level"] ?? "?";' "$ULTIMO")"
paso 'médico registra penicilina (amenaza la vida) → 201' 201 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J" -d "$PENI" "$ALTA"
paso 'médico repite la misma alergia activa → 409' 409 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J" -d "$PENI" "$ALTA"
paso 'severidad fuera del catálogo → 422' 422 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J" \
  -d '{"allergen_name":"Látex","allergy_type":"allergy","severity":"grave"}' "$ALTA"
paso 'paciente inexistente → 404' 404 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$B/api/v1/patients/00000000-0000-4000-8000-0000000000ff/allergy-alert"
paso 'médico consulta de nuevo → 200 (critical)' 200 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$ALERTA"
echo "      nivel: $(php -r '$d = json_decode($argv[1], true)["data"]; echo $d["level"], " · ", count($d["allergies"]), " alergia(s)";' "$ULTIMO")"
