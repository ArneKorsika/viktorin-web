#!/usr/bin/env bash
# One-time setup on a Mac. Run from anywhere:  bash scripts/setup.sh
source "$(dirname "${BASH_SOURCE[0]}")/config.sh"
cd "$REPO_DIR"

git rev-parse --git-dir >/dev/null 2>&1 || die "$REPO_DIR is not a git repo"

chmod +x scripts/*.sh .githooks/*
git config core.hooksPath .githooks
info "Git hooks enabled (.githooks/pre-commit runs scripts/snapshot.sh)"

check_db
info "Database connection OK ($DB_NAME via $(basename "$MYSQL"))"

bash scripts/snapshot.sh

echo
read -r -p "Activate the jupiterx-child theme now (Customizer settings are copied over)? [y/N] " ans
if [[ "$ans" =~ ^[Yy]$ ]]; then
  bash scripts/activate-child-theme.sh
  bash scripts/snapshot.sh >/dev/null
fi

echo
echo "✔ Setup complete. Make the first commit with:"
echo "    git add -A && git commit -m \"Initial snapshot of Viktorin site\""
