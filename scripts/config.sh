#!/usr/bin/env bash
# Shared settings + helpers. Sourced by the other scripts; don't run directly.
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_DIR="${WP_DIR:-/Applications/MAMP/htdocs/viktorin}"
LOCAL_URL="${LOCAL_URL:-http://localhost:8888/viktorin}"

# Our own code: paths under wp-content that are mirrored into the repo.
# Third-party plugins/themes are NOT tracked (see versions.txt instead).
TRACKED_CODE=(
  "plugins/superuser-core"
  "themes/jupiterx-child"
)

# Generated/cache folders inside uploads that shouldn't be versioned.
UPLOADS_EXCLUDES=(
  ".DS_Store"
  "elementor/css/"
  "elementor/thumbs/"
  "jupiterx/compiler/"
  "jupiterx/admin-compiler/"
  "cache/"
)

DB_FILE="$REPO_DIR/db/viktorin.sql"

die() { echo "✖ $*" >&2; exit 1; }
info() { echo "→ $*"; }

[ -f "$WP_DIR/wp-config.php" ] || die "wp-config.php not found in $WP_DIR (set WP_DIR=...)"

# ── Read DB credentials from wp-config.php ───────────────────────────────────
wpconf() {
  perl -ne "print \$1 if /define\(\s*['\"]$1['\"]\s*,\s*['\"](.*?)['\"]\s*\)\s*;/" "$WP_DIR/wp-config.php"
}
DB_NAME="$(wpconf DB_NAME)"
DB_USER="$(wpconf DB_USER)"
DB_PASSWORD="$(wpconf DB_PASSWORD)"
TABLE_PREFIX="$(perl -ne 'print $1 if /\$table_prefix\s*=\s*[\x27"](.*?)[\x27"]/' "$WP_DIR/wp-config.php")"
TABLE_PREFIX="${TABLE_PREFIX:-wp_}"

# ── Locate MAMP's MySQL client tools ─────────────────────────────────────────
find_bin() {
  local name="$1" c
  for c in /Applications/MAMP/Library/bin/"$name" /Applications/MAMP/Library/bin/mysql*/bin/"$name"; do
    [ -x "$c" ] && { echo "$c"; return; }
  done
  command -v "$name" || true
}
MYSQL="$(find_bin mysql)"
MYSQLDUMP="$(find_bin mysqldump)"
[ -n "$MYSQL" ] && [ -n "$MYSQLDUMP" ] || die "mysql/mysqldump not found (is MAMP installed?)"

# Credentials go in a temp option file so special characters in the password
# are safe and nothing shows up in `ps`.
MYCNF="$(mktemp -t viktorin-mycnf)"
chmod 600 "$MYCNF"
trap 'rm -f "$MYCNF"' EXIT
{
  echo "[client]"
  echo "user=\"$DB_USER\""
  printf 'password="%s"\n' "$(printf '%s' "$DB_PASSWORD" | sed 's/\\/\\\\/g; s/"/\\"/g')"
  if [ -S /Applications/MAMP/tmp/mysql/mysql.sock ]; then
    echo "socket=/Applications/MAMP/tmp/mysql/mysql.sock"
  else
    echo "host=127.0.0.1"
    echo "port=8889"
  fi
} > "$MYCNF"

mysql_q() { "$MYSQL" --defaults-extra-file="$MYCNF" "$DB_NAME" "$@"; }

check_db() {
  mysql_q -e "SELECT 1" >/dev/null 2>&1 || die "Can't connect to MySQL database '$DB_NAME'. Is MAMP running?"
}
