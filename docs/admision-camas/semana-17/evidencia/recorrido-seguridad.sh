#!/usr/bin/env bash
# Recorrido de seguridad (Semana 17, ASII-07) contra la API v1 desplegada en local: login JWT real, tenant, RBAC, guard y PostgreSQL.
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-seguridad.sh <base> <hospital-id> <otro-hospital-id> <paciente> <cama> [<base-con-HOSPITAL-caida>]
# Imprime «esperado / obtenido» por paso; nunca imprime tokens. Datos ficticios del seed de E2E.
CAB=$(mktemp); B=$1; H=$2; OTRO=$3; P=$4; CAMA=$5; CAIDA=$6; J='Content-Type: application/json'
token() { curl -s -H "X-Tenant-ID: $H" -H "$J" -d "{\"email\":\"$1\",\"password\":\"$E2E_PASSWORD\"}" "$B/api/v1/auth/login" \
  | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo $j["access_token"] ?? $j["data"]["access_token"] ?? "";'; }
paso() { # paso <descripción> <esperado> <curl args...>
  local d=$1 esperado=$2; shift 2
  local cuerpo obtenido; cuerpo=$(curl -s -D "$CAB" -w '\n%{http_code}' "$@"); obtenido=${cuerpo##*$'\n'}; ULTIMO=${cuerpo%$'\n'*}
  printf '%s  %-70s esperado %s, obtenido %s\n' "$([ "$obtenido" = "$esperado" ] && echo PASS || echo FAIL)" "$d" "$esperado" "$obtenido"
}
# Cambia un carácter del medio de la firma: el último solo lleva 4 bits de datos y, si es «w», cambiarlo por «x» deja la misma firma.
alterada() { local i=$((${#1} - 10)) c; c=${1:$i:1}; [ "$c" = A ] && c=B || c=A; echo "${1:0:$i}$c${1:$((i + 1))}"; }
campo() { php -r '$j = json_decode($argv[1], true); foreach (explode(".", $argv[2]) as $k) { $j = $j[$k] ?? null; } echo is_scalar($j) ? $j : json_encode($j);' "$ULTIMO" "$1"; }
MED=$(token "$E2E_MEDICO_EMAIL"); REC=$(token recepcion.e2e@hospital-ficticio.test)
[ -n "$MED" ] && [ -n "$REC" ] || { echo "FAIL  login de médico o recepción"; exit 1; }
CAMAS="$B/api/v1/beds/available"; ADM="$B/api/v1/admissions"; R=(-H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" -H "$J")
paso 'D01 sin token → 401' 401 -H "X-Tenant-ID: $H" "$CAMAS"
paso 'D01 token con la firma alterada → 401' 401 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $(alterada "$REC")" "$CAMAS"
paso 'D02 token del hospital A con X-Tenant-ID del B → 403' 403 -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $REC" "$CAMAS"
paso 'D03 médico sin camas.ver → 403' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$CAMAS"
paso 'D03 médico sin admisiones.registrar → 403' 403 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J" \
  -d "{\"patient_id\":\"$P\",\"bed_id\":\"$CAMA\",\"reason\":\"Neumonía\"}" "$ADM"
paso 'D05 patient_id como arreglo → 404, no 500' 404 -X POST "${R[@]}" -d "{\"patient_id\":[\"$P\"],\"bed_id\":\"$CAMA\",\"reason\":\"Neumonía\"}" "$ADM"
paso 'D05 reason como arreglo → 422, no 500' 422 -X POST "${R[@]}" -d "{\"patient_id\":\"$P\",\"bed_id\":\"$CAMA\",\"reason\":[\"Neumonía\"]}" "$ADM"
paso 'D06 motivo de 501 caracteres → 422' 422 -X POST "${R[@]}" -d "{\"patient_id\":\"$P\",\"bed_id\":\"$CAMA\",\"reason\":\"$(printf 'm%.0s' {1..501})\"}" "$ADM"
echo "      código: $(campo error.code) · campo: $(campo error.field)"
paso 'D04 el cuerpo pide otro autor y otra hora de inicio → 201' 201 -X POST "${R[@]}" \
  -d "{\"patient_id\":\"$P\",\"bed_id\":\"$CAMA\",\"reason\":\"Neumonía\",\"assigned_by_user_ref_id\":\"00000000-0000-4000-8000-000000000000\",\"started_at\":\"1999-01-01T00:00:00Z\"}" "$ADM"
echo "      inicio del servidor, no 1999: $([ "${ULTIMO#*1999-01-01}" = "$ULTIMO" ] && echo sí || echo NO)"
paso 'D08 listado de camas con X-Request-ID no confiable → 200' 200 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" -H 'X-Request-ID: <script>alert(1)</script>' "$CAMAS"
echo "      campos por cama: $(campo data.beds.0 | php -r 'echo implode(",", array_keys(json_decode(stream_get_contents(STDIN), true) ?: []));') · request_id: $(campo meta.request_id) · $(grep -i '^cache-control' "$CAB" | tr -d '\r')"
if [ -n "$CAIDA" ]; then
  paso 'D09 HOSPITAL caído → 503' 503 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" "$CAIDA/api/v1/beds/available"
  echo "      código: $(campo error.code) · contiene «SQLSTATE», host o puerto: $(grep -c -E 'SQLSTATE|127\.0\.0\.1|5999' <<<"$ULTIMO")"
fi
rm -f "$CAB"
