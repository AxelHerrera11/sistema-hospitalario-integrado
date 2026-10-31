#!/usr/bin/env bash
# Recorrido HTTP real contra la API v1 de Laravel (Semana 16, ASII-21): login JWT, tenant, RBAC, guard de #1154 y PostgreSQL (critical_alerts 000660).
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-alertas.sh <base> <hospital-id> <otro-hospital-id> <prueba> <paciente-1> <episodio-1> <paciente-2> <episodio-2>
# El episodio 1 tiene médica tratante (care_assignments real); el 2 no. Imprime «esperado / obtenido»; nunca imprime tokens. Datos ficticios.
B=$1; H=$2; OTRO=$3; T=$4; P1=$5; E1=$6; P2=$7; E2=$8; J='Content-Type: application/json'
token() { curl -s -H "X-Tenant-ID: $H" -H "$J" -d "{\"email\":\"$1\",\"password\":\"$E2E_PASSWORD\"}" "$B/api/v1/auth/login" \
  | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo $j["access_token"] ?? $j["data"]["access_token"] ?? "";'; }
paso() { # paso <descripción> <esperado> <curl args...>
  local d=$1 esperado=$2; shift 2
  local cuerpo obtenido; cuerpo=$(curl -s -w '\n%{http_code}' "$@"); obtenido=${cuerpo##*$'\n'}; ULTIMO=${cuerpo%$'\n'*}
  printf '%s  %-66s esperado %s, obtenido %s\n' "$([ "$obtenido" = "$esperado" ] && echo PASS || echo FAIL)" "$d" "$esperado" "$obtenido"
}
campo() { php -r '$d = json_decode($argv[1], true)["data"] ?? []; foreach (explode(".", $argv[2]) as $k) { $d = $d[$k] ?? null; } echo is_array($d) ? count($d) : var_export($d, true);' "$ULTIMO" "$1"; }
BIO=$(token bioquimico.e2e@hospital-ficticio.test); MED=$(token "$E2E_MEDICO_EMAIL"); JEF=$(token jefatura.e2e@hospital-ficticio.test); REC=$(token recepcion.e2e@hospital-ficticio.test)
[ -n "$BIO" ] && [ -n "$MED" ] && [ -n "$JEF" ] && [ -n "$REC" ] || { echo "FAIL  login de algún usuario ficticio"; exit 1; }
U="$B/api/v1/critical-alerts"; A=(-H "X-Tenant-ID: $H" -H "$J")
ev() { echo "{\"result_version_id\":\"$1\",\"lab_test_id\":\"$T\",\"patient_id\":\"$2\",\"episode_id\":\"$3\",\"value\":$4}"; }
paso 'sin token → 401' 401 -H "X-Tenant-ID: $H" "$U"
paso 'token del hospital A con X-Tenant-ID del B → 403' 403 -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $MED" "$U"
paso 'recepción sin alertas.ver → 403 RBAC' 403 "${A[@]}" -H "Authorization: Bearer $REC" "$U"
paso 'médico intenta publicar un resultado (sin lab-results.validate) → 403' 403 "${A[@]}" -H "Authorization: Bearer $MED" -X POST -d "$(ev e2e00000-0000-4000-8000-0000000000e1 $P1 $E1 32)" "$U/result-validated"
paso 'bioquímico publica glucosa 32 (crítico bajo 40) → 201' 201 "${A[@]}" -H "Authorization: Bearer $BIO" -X POST -d "$(ev e2e00000-0000-4000-8000-0000000000e1 $P1 $E1 32)" "$U/result-validated"
ALERTA=$(campo alert.critical_alert_id | tr -d "'"); echo "      dirección $(campo alert.direction), nivel $(campo alert.escalation_level), destinataria = médica tratante: $([ "$(campo alert.recipient_user_ref_id)" != NULL ] && echo sí || echo no)"
paso 'el mismo resultado otra vez → 200 duplicate' 200 "${A[@]}" -H "Authorization: Bearer $BIO" -X POST -d "$(ev e2e00000-0000-4000-8000-0000000000e1 $P1 $E1 32)" "$U/result-validated"
paso 'glucosa 95 → 200 not_critical' 200 "${A[@]}" -H "Authorization: Bearer $BIO" -X POST -d "$(ev e2e00000-0000-4000-8000-0000000000e2 $P1 $E1 95)" "$U/result-validated"
paso 'episodio de otro paciente → 422 EVENT_INVALID' 422 "${A[@]}" -H "Authorization: Bearer $BIO" -X POST -d "$(ev e2e00000-0000-4000-8000-0000000000e3 $P2 $E1 32)" "$U/result-validated"
paso 'paciente 2 sin médico tratante, glucosa 500 → 201 a la jefatura' 201 "${A[@]}" -H "Authorization: Bearer $BIO" -X POST -d "$(ev e2e00000-0000-4000-8000-0000000000e4 $P2 $E2 500)" "$U/result-validated"
echo "      nivel $(campo alert.escalation_level), destinatario $(campo alert.recipient_user_ref_id)"
paso 'médico ve su bandeja → 200' 200 "${A[@]}" -H "Authorization: Bearer $MED" "$U"; echo "      alertas pendientes: $(campo alerts)"
paso 'médico acusa leyendo 23 en lugar de 32 → 422 READ_BACK_MISMATCH' 422 "${A[@]}" -H "Authorization: Bearer $MED" -X POST -d "{\"read_back\":{\"patient_id\":\"$P1\",\"lab_test_id\":\"$T\",\"value\":23}}" "$U/$ALERTA/acknowledge"
paso 'médico acusa con la lectura correcta → 200' 200 "${A[@]}" -H "Authorization: Bearer $MED" -X POST -d "{\"read_back\":{\"patient_id\":\"$P1\",\"lab_test_id\":\"$T\",\"value\":32}}" "$U/$ALERTA/acknowledge"
paso 'segundo acuse → 409 ALERT_ALREADY_ACKNOWLEDGED' 409 "${A[@]}" -H "Authorization: Bearer $MED" -X POST -d "{\"read_back\":{\"patient_id\":\"$P1\",\"lab_test_id\":\"$T\",\"value\":32}}" "$U/$ALERTA/acknowledge"
paso 'médico pide la bandeja de la jefatura (sin alertas.supervisar) → 403' 403 "${A[@]}" -H "Authorization: Bearer $MED" "$U/service-head"
paso 'jefatura médica ve la bandeja del servicio → 200' 200 "${A[@]}" -H "Authorization: Bearer $JEF" "$U/service-head"; echo "      alertas pendientes: $(campo alerts)"
