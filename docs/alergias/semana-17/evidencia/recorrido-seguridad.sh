#!/usr/bin/env bash
# Recorrido de seguridad (Semana 17, ASII-12) contra la API v1 desplegada en local: login JWT real, tenant, RBAC, guard y PostgreSQL.
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-seguridad.sh <base> <hospital-id> <otro-hospital-id> <paciente> <otro-paciente> [<base-con-HOSPITAL-caida>]
# Imprime «esperado / obtenido» por paso; nunca imprime tokens. Datos ficticios del seed de E2E.
CAB=$(mktemp); B=$1; H=$2; OTRO=$3; P=$4; P2=$5; CAIDA=$6; J='Content-Type: application/json'
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
MED=$(token "$E2E_MEDICO_EMAIL"); ADM=$(token "$E2E_ADMIN_EMAIL"); REC=$(token recepcion.e2e@hospital-ficticio.test)
[ -n "$MED" ] && [ -n "$ADM" ] && [ -n "$REC" ] || { echo "FAIL  login de médico, administrador o recepción"; exit 1; }
ALERTA="$B/api/v1/patients/$P/allergy-alert"; ALTA="$B/api/v1/patients/$P/allergies"; A=(-H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED")
paso 'A01 sin token → 401' 401 -H "X-Tenant-ID: $H" "$ALERTA"
paso 'A01 token con la firma alterada → 401' 401 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $(alterada "$MED")" "$ALERTA"
paso 'A02 token del hospital A con X-Tenant-ID del B → 403' 403 -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $MED" "$ALERTA"
paso 'A03 admin_hospital sin alergias.ver → 403' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $ADM" "$ALERTA"
paso 'A03 recepción sin alergias.registrar → 403' 403 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" -H "$J" \
  -d '{"allergen_name":"Látex","allergy_type":"allergy","severity":"mild"}' "$ALTA"
paso 'A06 nombre de 201 caracteres → 422' 422 -X POST "${A[@]}" -H "$J" \
  -d "{\"allergen_name\":\"$(printf 'a%.0s' {1..201})\",\"allergy_type\":\"allergy\",\"severity\":\"mild\"}" "$ALTA"
echo "      código: $(campo error.code) · campo: $(campo error.field)"
paso 'A04 el cuerpo pide otro paciente, estado resuelto y otro autor → 201' 201 -X POST "${A[@]}" -H "$J" \
  -d "{\"allergen_name\":\"<img src=x onerror=alert(1)>\",\"allergy_type\":\"allergy\",\"severity\":\"severe\",\"local_patient_id\":\"$P2\",\"status\":\"resolved\",\"verified_by_user_ref_id\":\"00000000-0000-4000-8000-000000000000\"}" "$ALTA"
echo "      paciente guardado = ruta: $([ "$(campo data.local_patient_id)" = "$P" ] && echo sí || echo NO) · estado: $(campo data.status) · texto devuelto literal: $(campo data.allergen_name)"
paso 'A08 X-Request-ID no confiable (HTML) → 200' 200 "${A[@]}" -H 'X-Request-ID: <script>alert(1)</script>' "$ALERTA"
echo "      request_id reemplazado: $(campo meta.request_id) · $(grep -i '^cache-control' "$CAB" | tr -d '\r')"
paso 'A10 identificador que no es UUID → 404 JSON, sin traza' 404 "${A[@]}" "$B/api/v1/patients/1%20OR%201=1/allergy-alert"
echo "      código: $(campo error.code) · contiene «Exception» o «SQLSTATE»: $(grep -c -E 'Exception|SQLSTATE' <<<"$ULTIMO")"
if [ -n "$CAIDA" ]; then
  paso 'A09 HOSPITAL caído → 503, nunca «sin alergias»' 503 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$CAIDA/api/v1/patients/$P/allergy-alert"
  echo "      código: $(campo error.code) · contiene «SQLSTATE», host o puerto: $(grep -c -E 'SQLSTATE|127\.0\.0\.1|5999' <<<"$ULTIMO")"
fi
rm -f "$CAB"
