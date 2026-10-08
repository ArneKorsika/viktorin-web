#!/usr/bin/env bash
# Switch the site from Jupiter X to the jupiterx-child theme WITHOUT losing
# Customizer settings (header/footer, menus, colours live in theme_mods_<theme>).
# Undo with:  scripts/activate-child-theme.sh --undo
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
check_db
o="${TABLE_PREFIX}options"

if [ "${1:-}" = "--undo" ]; then
  mysql_q -e "UPDATE $o SET option_value='jupiterx' WHERE option_name='stylesheet';"
  echo "✔ Switched back to Jupiter X (parent)."
  exit 0
fi

[ -f "$WP_DIR/wp-content/themes/jupiterx-child/style.css" ] || die "jupiterx-child theme not found in MAMP"
current="$(mysql_q -N -e "SELECT option_value FROM $o WHERE option_name='stylesheet'")"
[ "$current" = "jupiterx-child" ] && { echo "Child theme is already active."; exit 0; }
[ "$current" = "jupiterx" ] || die "Active theme is '$current', expected 'jupiterx' – not touching it."

mysql_q <<SQL
DELETE FROM $o WHERE option_name='theme_mods_jupiterx-child';
INSERT INTO $o (option_name, option_value, autoload)
  SELECT 'theme_mods_jupiterx-child', option_value, autoload FROM $o WHERE option_name='theme_mods_jupiterx';
UPDATE $o SET option_value='jupiterx-child' WHERE option_name='stylesheet';
SQL
echo "✔ jupiterx-child is now active (Customizer settings copied from Jupiter X)."
echo "  Check $LOCAL_URL – if anything looks off: scripts/activate-child-theme.sh --undo"
