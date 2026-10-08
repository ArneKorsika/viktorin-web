# Viktorin – website

WordPress site for Viktorin Apartments. Built on Jupiter X (Artbees, yacht-rental demo) + Elementor + Crocoblock (JetEngine, JetSmartFilters, JetElements, JetTabs, JetPopup).

Local development happens in **MAMP**: `/Applications/MAMP/htdocs/viktorin` → http://localhost:8888/viktorin

## What's in this repo

| Path | What | Source of truth |
|---|---|---|
| `wp-content/plugins/superuser-core/` | Our custom plugin (login logo, SVG, single templates) | MAMP, mirrored here |
| `wp-content/themes/jupiterx-child/` | Child theme – site CSS/JS, template overrides | MAMP, mirrored here |
| `wp-content/uploads/` | Media library (without generated caches) | MAMP, mirrored here |
| `db/viktorin.sql` | Full database dump: Elementor layouts, JetEngine config, Customizer, content | MAMP DB |
| `versions.txt` | Versions of WP core + 3rd-party plugins/themes (not tracked in git) | generated |
| `scripts/` | snapshot / restore / setup tooling | this repo |

WordPress core and third-party plugins/themes are **not** in git – reinstall the versions listed in `versions.txt`.

## Workflow

1. Build the feature locally (Elementor, JetEngine, code in `superuser-core` / `jupiterx-child` inside MAMP).
2. `git commit` – the pre-commit hook runs `scripts/snapshot.sh`, which copies code, uploads and a fresh DB dump into the repo and stages them. MAMP must be running.
3. Push.

Skip the snapshot for a one-off commit (e.g. README only): `SKIP_SNAPSHOT=1 git commit ...`

### Going back to an earlier state
```bash
git checkout <commit>      # or a branch
bash scripts/restore.sh    # imports that DB + code + uploads into MAMP (backs up the current DB first)
```

### First-time setup on a machine
```bash
bash scripts/setup.sh
```
Enables the git hook, checks the DB connection, takes the first snapshot and optionally activates the child theme.

## Rules
- The DB dump is history/backup for **local** dev. Never import it into production – live bookings and content would be overwritten. Move changes to prod via code + Elementor/JetEngine exports, or redo them in the live admin.
- The dump contains user accounts (hashed passwords, emails) – keep the repo private.
- Never commit `wp-config.php` or credentials.
- Theme/CSS changes go in `jupiterx-child`, never in `jupiterx` (lost on update).
