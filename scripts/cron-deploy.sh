#!/usr/bin/env bash
#
# Pull-based deploy trigger, run every minute by cron on the server:
#   * * * * * bash $HOME/medfindsolutions.co.ke/scripts/cron-deploy.sh >/dev/null 2>&1
#
# GitHub Actions moves the `production` branch only after the tests pass. This script notices the
# move and runs scripts/deploy.sh. It needs no inbound SSH: the server fetches from GitHub using its
# read-only deploy key. Output goes to ~/medfind-deploy.log.
#
# A commit whose deploy failed (and was rolled back) is not retried every minute; push a new commit
# or delete ~/.medfind-deploy/failed-release to retry it.
set -uo pipefail

BRANCH="${1:-production}"
APP_DIR="${APP_DIR:-$HOME/medfindsolutions.co.ke}"
STATE_DIR="$HOME/.medfind-deploy"
LOG="$HOME/medfind-deploy.log"
mkdir -p "$STATE_DIR"

cd "$APP_DIR" || exit 1
git fetch --quiet origin "$BRANCH" 2>>"$LOG" || exit 0
TARGET="$(git rev-parse "origin/$BRANCH" 2>/dev/null)" || exit 0
[ "$TARGET" = "$(git rev-parse HEAD)" ] && exit 0
[ "$TARGET" = "$(cat "$STATE_DIR/failed-release" 2>/dev/null)" ] && exit 0
[ -d "$STATE_DIR/lock" ] && exit 0   # a deploy is already running

# Run the deploy script from the commit being deployed, copied out of the working tree so that
# updating the code cannot change the script while bash is still reading it.
SCRIPT="$(mktemp)"
git show "$TARGET:scripts/deploy.sh" > "$SCRIPT" 2>>"$LOG" || { rm -f "$SCRIPT"; exit 1; }

{
  echo
  echo "===== $(date '+%Y-%m-%d %H:%M:%S') deploying ${TARGET:0:8} ($BRANCH)"
  if bash "$SCRIPT" "$BRANCH"; then
    rm -f "$STATE_DIR/failed-release"
  else
    echo "$TARGET" > "$STATE_DIR/failed-release"
    echo "===== deploy of ${TARGET:0:8} FAILED; it will not be retried automatically"
  fi
} >> "$LOG" 2>&1
rm -f "$SCRIPT"

# Keep the log from growing forever.
if [ "$(wc -l < "$LOG")" -gt 5000 ]; then tail -n 2000 "$LOG" > "$LOG.tmp" && mv "$LOG.tmp" "$LOG"; fi
