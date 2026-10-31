#!/usr/bin/env bash
# Recorrido HTTP real contra la API v1 de Laravel (Semana 16, ASII-07): login JWT, tenant, RBAC, guard de #1154 y PostgreSQL.
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-admision.sh <base> <hospital-id> <otro-hospital-id> <paciente-1> <paciente-2>
# Imprime «esperado / obtenido» por paso; nunca imprime tokens. Datos ficticios del seed de E2E más un usuario de recepción.
B=$1; H=$2; OTRO=$3; P1=$4; P2=$5; J='Content-Type: application/json'
token() { curl -s -H "X-Tenant-ID: $H" -H "$J" -d "{\"email\":\"$1\",\"password\":\"$E2E_PASSWORD\"}" "$B/api/v1/auth/login" \
  | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo $j["access_token"] ?? $j["data"]["access_token"] ?? "";'; }
paso() { # paso <descripción> <esperado> <curl args...>
  local d=$1 esperado=$2; shift 2
  local cuerpo obtenido; cuerpo=$(curl -s -w '\n%{http_code}' "$@"); obtenido=${cuerpo##*$'\n'}; ULTIMO=${cuerpo%$'\n'*}
  printf '%s  %-66s esperado %s, obtenido %s\n' "$([ "$obtenido" = "$esperado" ] && echo PASS || echo FAIL)" "$d" "$esperado" "$obtenido"
}
REC=$(token recepcion.e2e@hospital-ficticio.test); MED=$(token "$E2E_MEDICO_EMAIL")
[ -n "$REC" ] && [ -n "$MED" ] || { echo "FAIL  login de recepción o médico"; exit 1; }
CAMAS="$B/api/v1/beds/available"; ADM="$B/api/v1/admissions"
paso 'sin token → 401' 401 -H "X-Tenant-ID: $H" "$CAMAS"
paso 'token del hospital A con X-Tenant-ID del B → 403' 403 -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $REC" "$CAMAS"
paso 'médico sin camas.ver → 403 RBAC' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$CAMAS"
paso 'recepción lista camas UCI → 200' 200 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" "$CAMAS?type=icu"
UCI=$(php -r 'echo json_decode($argv[1], true)["data"]["beds"][0]["bed_id"] ?? "";' "$ULTIMO")
echo "      camas UCI disponibles: $(php -r 'echo count(json_decode($argv[1], true)["data"]["beds"]);' "$ULTIMO")"
paso 'médico intenta admitir (sin admisiones.registrar) → 403' 403 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J" \
  -d "{\"patient_id\":\"$P1\",\"bed_id\":\"$UCI\",\"reason\":\"Sepsis\"}" "$ADM"
paso 'recepción admite al paciente 1 en la UCI → 201' 201 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" -H "$J" \
  -d "{\"patient_id\":\"$P1\",\"bed_id\":\"$UCI\",\"reason\":\"Sepsis\"}" "$ADM"
paso 'paciente 2 en la misma cama → 409 BED_NOT_AVAILABLE' 409 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" -H "$J" \
  -d "{\"patient_id\":\"$P2\",\"bed_id\":\"$UCI\",\"reason\":\"Fractura\"}" "$ADM"
paso 'paciente 1 otra vez → 409 PATIENT_ALREADY_ADMITTED' 409 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" -H "$J" \
  -d "{\"patient_id\":\"$P1\",\"bed_id\":\"$UCI\",\"reason\":\"Sepsis\"}" "$ADM"
paso 'sin motivo → 422' 422 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" -H "$J" -d "{\"patient_id\":\"$P2\",\"bed_id\":\"$UCI\",\"reason\":\" \"}" "$ADM"
paso 'recepción lista camas UCI de nuevo → 200 (ninguna)' 200 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" "$CAMAS?type=icu"
echo "      camas UCI disponibles: $(php -r 'echo count(json_decode($argv[1], true)["data"]["beds"]);' "$ULTIMO")"
