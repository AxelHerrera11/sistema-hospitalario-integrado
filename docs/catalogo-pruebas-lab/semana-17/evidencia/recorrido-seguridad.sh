#!/usr/bin/env bash
# Recorrido de seguridad (Semana 17, ASII-17) contra la API v1 desplegada en local: login JWT real, tenant, RBAC, guard y PostgreSQL.
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-seguridad.sh <base> <hospital-id> <otro-hospital-id> [<base-con-HOSPITAL-caida>]
# Imprime «esperado / obtenido» por paso; nunca imprime tokens. Datos ficticios del seed de E2E.
CAB=$(mktemp); B=$1; H=$2; OTRO=$3; CAIDA=$4; J='Content-Type: application/json'
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
MED=$(token "$E2E_MEDICO_EMAIL"); REC=$(token recepcion.e2e@hospital-ficticio.test); SUP=$(token superadmin.e2e@hospital-ficticio.test)
[ -n "$MED" ] && [ -n "$REC" ] && [ -n "$SUP" ] || { echo "FAIL  login de médico, recepción o superadmin"; exit 1; }
CAT="$B/api/v1/lab-tests"; A=(-H "X-Tenant-ID: $H" -H "Authorization: Bearer $SUP" -H "$J"); HOY=$(date +%F)
glu() { echo "{\"code\":\"GLU\",\"name\":\"Glucosa\",\"specimen_type\":\"serum\",\"min_volume_ml\":0.5,\"result_type\":\"numeric\",\"unit\":\"mg/dL\",\"reference_low\":70,\"reference_high\":100,\"critical_low\":40,\"critical_high\":400,\"valid_from\":\"$HOY\"$1}"; }
paso 'L01 sin token → 401' 401 -H "X-Tenant-ID: $H" "$CAT"
paso 'L01 token con la firma alterada → 401' 401 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $(alterada "$SUP")" "$CAT"
paso 'L02 token del hospital A con X-Tenant-ID del B → 403' 403 -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $MED" "$CAT"
paso 'L03 recepción sin laboratorio.catalogo.ver → 403' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" "$CAT"
paso 'L03 médico consulta pero no administra → 403' 403 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J" -d "$(glu)" "$CAT"
paso 'L05 unidad como arreglo → 422, no 500' 422 -X POST "${A[@]}" -d "$(glu | sed 's#"unit":"mg/dL"#"unit":["mg/dL"]#')" "$CAT"
paso 'L05 crítico alto 1e400 (infinito) → 422, no 500' 422 -X POST "${A[@]}" -d "$(glu | sed 's#"critical_high":400#"critical_high":1e400#')" "$CAT"
paso 'L06 nombre de 161 caracteres → 422' 422 -X POST "${A[@]}" -d "$(glu | sed "s#\"Glucosa\"#\"$(printf 'n%.0s' {1..161})\"#")" "$CAT"
echo "      código: $(campo error.code) · campo: $(campo error.field)"
paso 'L04 el cuerpo pide otro autor, versión 99 e inactiva → 201' 201 -X POST "${A[@]}" \
  -d "$(glu ',"created_by_user_ref_id":"00000000-0000-4000-8000-000000000000","version_no":99,"status":"inactive"')" "$CAT"
echo "      versión guardada: $(campo data.version_no)"
paso 'L07 misma prueba con vigencia solapada → 409' 409 -X POST "${A[@]}" -d "$(glu)" "$CAT"
paso 'L08 médico consulta con X-Request-ID no confiable → 200' 200 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H 'X-Request-ID: <script>alert(1)</script>' "$CAT?search=gluc"
echo "      pruebas: $(campo data.lab_tests | php -r 'echo count(json_decode(stream_get_contents(STDIN), true) ?: []);') · request_id: $(campo meta.request_id) · $(grep -i '^cache-control' "$CAB" | tr -d '\r')"
if [ -n "$CAIDA" ]; then
  paso 'L09 HOSPITAL caído → 503' 503 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$CAIDA/api/v1/lab-tests"
  echo "      código: $(campo error.code) · contiene «SQLSTATE», host o puerto: $(grep -c -E 'SQLSTATE|127\.0\.0\.1|5999' <<<"$ULTIMO")"
fi
rm -f "$CAB"
