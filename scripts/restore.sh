#!/usr/bin/env bash
# Put the repo's state back into the local MAMP site (the reverse of snapshot.sh):
#   - imports db/viktorin.sql (REPLACES the local database tables)
#   - copies tracked code and uploads back into MAMP
# Typical use: `git checkout <commit-or-branch>` then `scripts/restore.sh`.
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
check_db
[ -f "$DB_FILE" ] || die "No $DB_FILE in the repo"

echo "This will OVERWRITE the local database '$DB_NAME', our code and uploads in:"
echo "  $WP_DIR"
echo "with the version from the repo ($(git -C "$REPO_DIR" log -1 --format='%h %s' 2>/dev/null || echo 'working tree'))."
if [ "${1:-}" != "--yes" ]; then
  read -r -p "Continue? [y/N] " ans
  [[ "$ans" =~ ^[Yy]$ ]] || { echo "Cancelled."; exit 1; }
fi

# Safety net: dump the current DB first (gitignored).
mkdir -p "$REPO_DIR/db/backups"
backup="$REPO_DIR/db/backups/before-restore-$(date +%Y%m%d-%H%M%S).sql"
"$MYSQLDUMP" --defaults-extra-file="$MYCNF" --single-transaction --no-tablespaces "$DB_NAME" > "$backup"
info "Current DB backed up → db/backups/$(basename "$backup")"

mysql_q < "$DB_FILE"
info "Database imported"

for path in "${TRACKED_CODE[@]}"; do
  src="$REPO_DIR/wp-content/$path"
  [ -d "$src" ] || continue
  mkdir -p "$WP_DIR/wp-content/$path"
  rsync -a --delete --exclude '.DS_Store' "$src/" "$WP_DIR/wp-content/$path/"
done
info "Code restored"

excludes=()
for e in "${UPLOADS_EXCLUDES[@]}"; do excludes+=(--exclude "$e"); done
rsync -a --delete "${excludes[@]}" "$REPO_DIR/wp-content/uploads/" "$WP_DIR/wp-content/uploads/"
# Force Elementor to rebuild its CSS from the restored data.
rm -rf "$WP_DIR/wp-content/uploads/elementor/css"
mysql_q -e "DELETE FROM ${TABLE_PREFIX}postmeta WHERE meta_key='_elementor_css'; DELETE FROM ${TABLE_PREFIX}options WHERE option_name IN ('_elementor_global_css','elementor-custom-breakpoints-files');"
info "Uploads restored, Elementor CSS cache cleared"

echo "✔ Restore done. Third-party plugin versions expected by this commit are in versions.txt."
