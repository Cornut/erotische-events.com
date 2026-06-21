#!/bin/bash
#
# deploy.sh – Git-Deployment für SiteGround
#
# Klont das Repo beim ersten Lauf direkt ins Ziel-Verzeichnis (ohne Unterordner)
# und aktualisiert es bei jedem weiteren Lauf per fetch + reset.
#
# Aufruf:  ./deploy.sh [branch]
# Beispiel: ./deploy.sh main
# Ohne Parameter: Branch automatisch erkennen (main/master)
#

set -euo pipefail

# ---- Konfiguration ---------------------------------------------------------
REPO_URL="https://github.com/Cornut/erotische-events.com.git"
DEPLOY_DIR="$HOME/www/corneld1.sg-host.com/laravel-app"   # ggf. anpassen
BRANCH="${1:-}"   # 1. Parameter = Branch; leer = automatisch erkennen
# ---------------------------------------------------------------------------

echo "==> Deploy nach: $DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"
cd "$DEPLOY_DIR"

# Branch automatisch ermitteln, falls nicht gesetzt
detect_branch() {
    if [ -n "$BRANCH" ]; then
        echo "$BRANCH"
    else
        git ls-remote --symref "$REPO_URL" HEAD \
            | awk '/^ref:/ {sub("refs/heads/", "", $2); print $2; exit}'
    fi
}

if [ -d ".git" ]; then
    # ---- Update: bestehendes Repo aktualisieren ----
    echo "==> Bestehendes Repo gefunden – aktualisiere ..."
    BRANCH="$(detect_branch)"
    git fetch origin "$BRANCH"
    git reset --hard "origin/$BRANCH"
    git clean -fd
    echo "==> Aktualisiert auf origin/$BRANCH"
else
    # ---- Erstinstallation ----
    echo "==> Kein Repo vorhanden – initialisiere ..."
    BRANCH="$(detect_branch)"
    git init -q
    git remote add origin "$REPO_URL"
    git fetch origin "$BRANCH"
    git checkout -t "origin/$BRANCH" -f
    echo "==> Erstinstallation abgeschlossen (Branch: $BRANCH)"
fi

echo "==> Aktueller Stand:"
git log -1 --oneline
echo "==> Fertig."