#!/usr/bin/env bash
# Recorrido de seguridad (Semana 17, ASII-21) contra la API v1 desplegada en local: login JWT real, tenant, RBAC, guard y PostgreSQL.
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-seguridad.sh <base> <hospital-id> <otro-hospital-id> <prueba> <paciente> <episodio> <episodio-de-otro-paciente> [<base-con-HOSPITAL-caida>]
# Datos: datos de la Semana 16 (`datos-e2e-alertas.sh`); la médica del seed es la tratante del episodio. Nunca imprime tokens.
CAB=$(mktemp); B=$1; H=$2; OTRO=$3; T=$4; P=$5; E=$6; E2=$7; CAIDA=$8; J='Content-Type: application/json'
token() { curl -s -H "X-Tenant-ID: $H" -H "$J" -d "{\"email\":\"$1\",\"password\":\"$E2E_PASSWORD\"}" "$B/api/v1/auth/login" \
  | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo $j["access_token"] ?? $j["data"]["access_token"] ?? "";'; }
paso() { # paso <descripción> <esperado> <curl args...>
  local d=$1 esperado=$2; shift 2
  local cuerpo obtenido; cuerpo=$(curl -s -D "$CAB" -w '\n%{http_code}' "$@"); obtenido=${cuerpo##*$'\n'}; ULTIMO=${cuerpo%$'\n'*}
  printf '%s  %-70s esperado %s, obtenido %s\n' "$([ "$obtenido" = "$esperado" ] && echo PASS || echo FAIL)" "$d" "$esperado" "$obtenido"
}
campo() { php -r '$j = json_decode($argv[1], true); foreach (explode(".", $argv[2]) as $k) { $j = $j[$k] ?? null; } echo is_scalar($j) ? $j : json_encode($j);' "$ULTIMO" "$1"; }
MED=$(token "$E2E_MEDICO_EMAIL"); BIO=$(token bioquimico.e2e@hospital-ficticio.test); JEF=$(token jefatura.e2e@hospital-ficticio.test); REC=$(token recepcion.e2e@hospital-ficticio.test)
[ -n "$MED" ] && [ -n "$BIO" ] && [ -n "$JEF" ] && [ -n "$REC" ] || { echo "FAIL  login de médica, bioquímico, jefatura o recepción"; exit 1; }
AL="$B/api/v1/critical-alerts"; BQ=(-H "X-Tenant-ID: $H" -H "Authorization: Bearer $BIO" -H "$J"); M=(-H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J")
evento() { echo "{\"result_version_id\":\"$1\",\"lab_test_id\":\"$T\",\"patient_id\":\"$P\",\"episode_id\":\"${3:-$E}\",\"value\":${2:-32}$4}"; }
paso 'C01 sin token → 401' 401 -H "X-Tenant-ID: $H" "$AL"
paso 'C01 token con la firma alterada → 401' 401 -H "X-Tenant-ID: $H" -H "Authorization: Bearer ${MED%?}x" "$AL"
paso 'C02 token del hospital A con X-Tenant-ID del B → 403' 403 -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $MED" "$AL"
paso 'C03 médica sin lab-results.validate publica un resultado → 403' 403 -X POST "${M[@]}" -d "$(evento "$(uuidgen)")" "$AL/result-validated"
paso 'C03 recepción sin alertas.ver → 403' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $REC" "$AL"
paso 'C03 médica sin alertas.supervisar pide la bandeja de jefatura → 403' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$AL/service-head"
paso 'C05 result_version_id como arreglo → 422, no 500' 422 -X POST "${BQ[@]}" -d "$(evento "$(uuidgen)" | sed 's/"result_version_id":"\([^"]*\)"/"result_version_id":["\1"]/')" "$AL/result-validated"
paso 'C05 valor 1e400 (infinito) → 422, no 500' 422 -X POST "${BQ[@]}" -d "$(evento "$(uuidgen)" 1e400)" "$AL/result-validated"
paso 'C06 episodio de otro paciente → 422 (trigger de HOSPITAL)' 422 -X POST "${BQ[@]}" -d "$(evento "$(uuidgen)" 32 "$E2")" "$AL/result-validated"
paso 'C04 el cuerpo pide otro destinatario, nivel 2 y acusada → 201' 201 -X POST "${BQ[@]}" \
  -d "$(evento "$(uuidgen)" 32 "$E" ',"recipient_user_ref_id":null,"escalation_level":2,"status":"acknowledged"')" "$AL/result-validated"
ID=$(campo data.alert.critical_alert_id); echo "      destinataria: $([ -n "$(campo data.alert.recipient_user_ref_id)" ] && echo 'médica tratante' || echo NINGUNA) · nivel: $(campo data.alert.escalation_level) · estado: $(campo data.alert.status)"
LEE="{\"read_back\":{\"patient_id\":\"$P\",\"lab_test_id\":\"$T\",\"value\":32},\"acknowledged_by_user_ref_id\":\"00000000-0000-4000-8000-000000000000\"}"
paso 'C07 jefatura (no tratante) acusa por la ruta común → 403' 403 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $JEF" -H "$J" -d "$LEE" "$AL/$ID/acknowledge"
paso 'C08 médica tratante con una lectura equivocada → 422' 422 -X POST "${M[@]}" -d "${LEE/\"value\":32/\"value\":23}" "$AL/$ID/acknowledge"
paso 'C08 médica tratante con la lectura correcta y otro acusador → 200' 200 -X POST "${M[@]}" -d "$LEE" "$AL/$ID/acknowledge"
echo "      estado: $(campo data.alert.status) · acusada por la tratante: $([ "$(campo data.alert.acknowledged_by_user_ref_id)" = "$(campo data.alert.recipient_user_ref_id)" ] && echo sí || echo NO)"
paso 'C09 acusar de nuevo → 409' 409 -X POST "${M[@]}" -d "$LEE" "$AL/$ID/acknowledge"
paso 'C10 bandeja con X-Request-ID no confiable → 200' 200 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H 'X-Request-ID: <script>alert(1)</script>' "$AL"
echo "      request_id: $(campo meta.request_id) · $(grep -i '^cache-control' "$CAB" | tr -d '\r')"
if [ -n "$CAIDA" ]; then
  paso 'C11 HOSPITAL caído → 503' 503 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$CAIDA/api/v1/critical-alerts"
  echo "      código: $(campo error.code) · contiene «SQLSTATE», host o puerto: $(grep -c -E 'SQLSTATE|127\.0\.0\.1|5999' <<<"$ULTIMO")"
fi
rm -f "$CAB"
