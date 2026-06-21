#!/bin/bash
#
# deployPublic.sh – public/ per rsync auf SiteGround (Web-Root)
#
# Aufruf:  ./deployPublic.sh [build|all]
#   build  – nur public/build/ (Standard, Vite-Assets)
#   all    – gesamtes public/
#
# Voraussetzung: npm run build lokal ausgeführt (für build).
#

set -euo pipefail

# ---- Konfiguration ---------------------------------------------------------
SSH_PORT="18765"
SSH_USER="u51-6xrgsg83y6qn"
SSH_HOST="gnldm1110.siteground.biz"
SSH_KEY="${HOME}/.ssh/id_rsa_erotic"
REMOTE="${SSH_USER}@${SSH_HOST}"
REMOTE_DIR="~/www/corneld1.sg-host.com/public_html"
RSYNC_SSH="ssh -p ${SSH_PORT} -i ${SSH_KEY}"
# ---------------------------------------------------------------------------

TARGET="${1:-build}"

echo "==> Ziel: ${REMOTE}:${REMOTE_DIR}"

case "$TARGET" in
    build)
        echo "==> Sync public/build/ ..."
        rsync -avz -e "$RSYNC_SSH" public/build/ "${REMOTE}:${REMOTE_DIR}/build/"
        ;;
    all)
        echo "==> Sync public/ ..."
        rsync -avz -e "$RSYNC_SSH" public/ "${REMOTE}:${REMOTE_DIR}/"
        ;;
    *)
        echo "Usage: $0 [build|all]" >&2
        exit 1
        ;;
esac

echo "==> Fertig."
