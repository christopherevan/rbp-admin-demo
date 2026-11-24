#!/usr/bin/env bash
# Runs on the VPS as root through sudo (called by the unprivileged kinetra-deploy
# user over Tailscale SSH). Reads the pinned GHCR image reference staged in the
# deploy user's home directory, pulls and starts the containers, verifies /up,
# and rolls back to the previous image if the new release does not become healthy.
set -euo pipefail

APP_DIR="/opt/kinetra-admin-demo"
ENV_FILE="$APP_DIR/.env"
COMPOSE_FILE="$APP_DIR/docker-compose.yml"
IMAGE_PATTERN='^ghcr\.io/christopherevan/[a-z0-9._-]+:sha-[0-9a-f]{7,40}$'
HEALTH_TIMEOUT_SECONDS=45

HOME_DIR="$(getent passwd "${SUDO_USER:?run this through sudo}" | cut -d: -f6)"
RELEASE_FILE="$HOME_DIR/release.image"

if [[ ! -f "$RELEASE_FILE" || -L "$RELEASE_FILE" ]]; then
  echo "No release image file found at $RELEASE_FILE" >&2
  exit 1
fi

NEW_IMAGE="$(tr -d '\r\n[:space:]' < "$RELEASE_FILE")"
rm -f "$RELEASE_FILE"

if [[ ! "$NEW_IMAGE" =~ $IMAGE_PATTERN ]]; then
  echo "Refusing unexpected image reference: $NEW_IMAGE" >&2
  exit 1
fi

if [[ ! -f "$COMPOSE_FILE" || ! -f "$ENV_FILE" ]]; then
  echo "Missing $COMPOSE_FILE or $ENV_FILE. Provision $APP_DIR first." >&2
  exit 1
fi

read_env() {
  local key="$1"
  local default="${2:-}"
  local val
  val="$(grep -E "^${key}=" "$ENV_FILE" | tail -n 1 | cut -d= -f2- | tr -d '\r"' || true)"
  echo "${val:-$default}"
}

set_env_image() {
  local image="$1"
  if grep -q '^APP_IMAGE=' "$ENV_FILE"; then
    sed -i "s|^APP_IMAGE=.*|APP_IMAGE=${image}|" "$ENV_FILE"
  else
    printf '\nAPP_IMAGE=%s\n' "$image" >> "$ENV_FILE"
  fi
}

PREVIOUS_IMAGE="$(read_env APP_IMAGE "")"
SERVER_NAME="$(read_env SERVER_NAME ":80")"
HTTP_BIND="$(read_env HTTP_BIND "8081")"
HTTPS_BIND="$(read_env HTTPS_BIND "8443")"

check_health() {
  local bind_addr port
  if [[ "$SERVER_NAME" == ":80" || -z "$SERVER_NAME" ]]; then
    if [[ "$HTTP_BIND" == *:* ]]; then
      bind_addr="$HTTP_BIND"
    else
      bind_addr="127.0.0.1:${HTTP_BIND}"
    fi
    curl --silent --fail --max-time 2 "http://${bind_addr}/up" >/dev/null
  else
    port="${HTTPS_BIND##*:}"
    curl --silent --fail --insecure --max-time 2 \
      --resolve "${SERVER_NAME}:${port}:127.0.0.1" \
      "https://${SERVER_NAME}:${port}/up" >/dev/null
  fi
}

is_healthy() {
  for ((i = 0; i < HEALTH_TIMEOUT_SECONDS; i++)); do
    if check_health; then
      return 0
    fi
    sleep 1
  done
  return 1
}

cd "$APP_DIR"
set_env_image "$NEW_IMAGE"

docker compose pull
docker compose up -d --no-build --remove-orphans

if is_healthy; then
  docker image prune -f >/dev/null || true
  echo "Deployed $NEW_IMAGE."
  exit 0
fi

echo "The new release ($NEW_IMAGE) did not become healthy within ${HEALTH_TIMEOUT_SECONDS}s." >&2
if [[ -n "$PREVIOUS_IMAGE" && "$PREVIOUS_IMAGE" != "$NEW_IMAGE" ]]; then
  echo "Rolling back to $PREVIOUS_IMAGE." >&2
  set_env_image "$PREVIOUS_IMAGE"
  docker compose up -d --no-build --remove-orphans
fi
exit 1
