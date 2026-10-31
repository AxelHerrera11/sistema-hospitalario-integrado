#!/usr/bin/env bash
# Despliegue on-premise de un micro-HIS con releases inmutables y rollback (Semana 17, guía de despliegue §2–§4).
#
#   bash micro-his/qa-calidad-versiones/bin/desplegar-micro-his.sh <destino> <commit> <puerto> <ruta-salud> <rutas...>
#
# Ejemplo (expediente médico, que reutiliza RBAC, Gobernanza y Pacientes):
#   bash .../desplegar-micro-his.sh /srv/his-expediente origin/develop 8096 /ui/ \
#     micro-his/expediente-medico micro-his/rbac micro-his/gobernanza-auditoria micro-his/pacientes config/rbac.php
#
# Cada release se exporta con `git archive` del commit indicado (nunca del árbol de trabajo) a
# <destino>/releases/<sha>; <destino>/current apunta al release activo y <destino>/shared guarda los datos, que no
# cambian entre releases. Si la verificación falla, current vuelve al release anterior. Cada paso imprime su hora.
# SIMULACRO=1 detiene el servidor al terminar (ensayos y evidencias).
set -euo pipefail
dest=$1 commit=$2 port=$3 health=$4; shift 4
sha=$(git rev-parse --short=12 "$commit^{commit}")
log() { printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S %z')" "$*"; }

log "P3 plataforma: $(php -r 'echo "PHP ", PHP_VERSION, " pdo_sqlite=", extension_loaded("pdo_sqlite") ? "sí" : "no";')"
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") && extension_loaded("pdo_sqlite") ? 0 : 1);' || { log "ABORTADO: PHP < 8.2 o sin pdo_sqlite"; exit 1; }
mkdir -p "$dest/releases" "$dest/shared"
previous=$(cat "$dest/current" 2>/dev/null || true)
log "release activo antes del despliegue: ${previous:-ninguno}"

release="$dest/releases/$sha"
if [ ! -d "$release" ]; then
  mkdir -p "$release"
  git archive --format=tar "$sha" -- "$@" | tar -x -C "$release"
  log "release $sha exportado con git archive ($(find "$release" -type f | wc -l) archivos)"
else
  log "release $sha ya existía; se reutiliza sin modificarlo"
fi
lint=0
while IFS= read -r -d '' f; do php -l "$f" > /dev/null 2>&1 || { log "error de sintaxis: ${f#"$release"/}"; lint=1; }; done < <(find "$release" -name '*.php' -print0)
[ "$lint" -eq 0 ] && log "sintaxis PHP del release: OK"

verify() { # inicia el servidor del release en el puerto y comprueba la ruta de salud
  local rel=$1 dir="$1/$2/public" code
  EXPEDIENTE_DEMO_DIR="$dest/shared" QA_DEMO_DB="$dest/shared/qa.sqlite" \
    php -S "127.0.0.1:$port" -t "$dir" "$dir/index.php" > "$dest/shared/server-$port.log" 2>&1 &
  echo $! > "$dest/shared/server.pid"
  for _ in $(seq 1 40); do curl -s -o /dev/null "http://127.0.0.1:$port/" && break; sleep 0.2; done
  code=$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$port$health")
  log "V1 salud $health en $(basename "$rel") → HTTP $code"
  [ "$code" = 200 ]
}
stop() { kill "$(cat "$dest/shared/server.pid")" 2>/dev/null || true; wait "$(cat "$dest/shared/server.pid")" 2>/dev/null || true; }

if [ "$lint" -eq 0 ] && verify "$release" "$1"; then
  echo "$sha" > "$dest/current"
  log "DESPLEGADO: current → $sha (servidor en 127.0.0.1:$port, pid $(cat "$dest/shared/server.pid"))"
  [ "${SIMULACRO:-0}" = 1 ] && { stop; log "simulacro: servidor detenido"; }
else
  stop
  log "FALLÓ la verificación de $sha"
  if [ -n "$previous" ]; then
    verify "$dest/releases/$previous" "$1" && log "ROLLBACK: current sigue en $previous y responde" || log "ROLLBACK FALLIDO: revisar $dest/shared"
    [ "${SIMULACRO:-0}" = 1 ] && { stop; log "simulacro: servidor detenido"; }
  fi
  exit 2
fi
