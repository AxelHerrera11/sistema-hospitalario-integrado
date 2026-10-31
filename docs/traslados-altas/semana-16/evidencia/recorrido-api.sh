#!/usr/bin/env bash
# Recorrido HTTP real contra la API v1 de Laravel (Semana 16): JWT, tenant y RBAC reales; HOSPITAL en PostgreSQL desechable.
# Uso: E2E_PASSWORD=<clave ficticia local> bash recorrido-api.sh <base> <hospital-id> <otro-hospital-id> <estancia> <cama-libre>
# Imprime «código esperado / obtenido» por paso; no imprime tokens. Fase: «pendiente» o «activada» (activación simulada local).
B=$1; H=$2; OTRO=$3; E=$4; CAMA=$5; FASE=${FASE:-pendiente}
token() { curl -s -H "X-Tenant-ID: $H" -H 'Content-Type: application/json' -d "{\"email\":\"$1\",\"password\":\"$E2E_PASSWORD\"}" "$B/api/v1/auth/login" \
  | php -r '$j = json_decode(stream_get_contents(STDIN), true); echo $j["access_token"] ?? $j["data"]["access_token"] ?? "";'; }
paso() { # paso <descripción> <esperado> <curl args...>
  local d=$1 esperado=$2; shift 2
  local obtenido; obtenido=$(curl -s -o /dev/null -w '%{http_code}' "$@")
  printf '%s  %-62s esperado %s, obtenido %s\n' "$([ "$obtenido" = "$esperado" ] && echo PASS || echo FAIL)" "$d" "$esperado" "$obtenido"
}
ENF=$(token enfermera@prueba.local); MED=$(token medico@prueba.local)
[ -n "$ENF" ] && [ -n "$MED" ] || { echo "FAIL  login de enfermera o médico"; exit 1; }
J='Content-Type: application/json'
TRASLADO="{\"estancia_esperada_id\":\"$E\",\"cama_destino_id\":\"$CAMA\",\"motivo\":\"Aislamiento\"}"
paso 'sin token → 401' 401 -X POST -H "X-Tenant-ID: $H" -H "$J" -d "$TRASLADO" "$B/api/v1/traslados"
paso 'token del hospital A con X-Tenant-ID del B → 403' 403 -X POST -H "X-Tenant-ID: $OTRO" -H "Authorization: Bearer $ENF" -H "$J" -d "$TRASLADO" "$B/api/v1/traslados"
if [ "$FASE" = pendiente ]; then
  paso 'enfermería traslada con el permiso en pending → 403' 403 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $ENF" -H "$J" -d "$TRASLADO" "$B/api/v1/traslados"
  paso 'médico consulta con el permiso en pending → 403' 403 -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" "$B/api/v1/estancias/$E/internacion"
  exit 0
fi
paso 'enfermería traslada → 201' 201 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $ENF" -H "$J" -d "$TRASLADO" "$B/api/v1/traslados"
D=$(curl -s -H "X-Tenant-ID: $H" -H "Authorization: Bearer $ENF" "$B/api/v1/estancias/$E/internacion" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["estancia_abierta_id"] ?? "";')
paso 'enfermería repite el traslado (estancia ya no vigente) → 409' 409 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $ENF" -H "$J" -d "$TRASLADO" "$B/api/v1/traslados"
paso 'enfermería intenta el alta (rol propuesto: médico) → 403' 403 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $ENF" -H "$J" -d "{\"estancia_esperada_id\":\"$D\"}" "$B/api/v1/altas"
paso 'médico da el alta de la nueva estancia → 200' 200 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J" -d "{\"estancia_esperada_id\":\"$D\"}" "$B/api/v1/altas"
paso 'médico repite el alta → 409' 409 -X POST -H "X-Tenant-ID: $H" -H "Authorization: Bearer $MED" -H "$J" -d "{\"estancia_esperada_id\":\"$D\"}" "$B/api/v1/altas"
echo "estancia destino: $D"
