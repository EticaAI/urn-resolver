#!/bin/bash
#===============================================================================
#
#          FILE:  sync-node-a.sh
#
#         USAGE:  ./scripts/sync-node-a.sh
#
#   DESCRIPTION:  ---
#
#       OPTIONS:  ---
#
#  REQUIREMENTS:  ---
#          BUGS:  ---
#         NOTES:  ---
#        AUTHOR:  Emerson Rocha <rocha[at]ieee.org>
#       COMPANY:  EticaAI
#       LICENSE:  Public Domain dedication
#                 SPDX-License-Identifier: Unlicense
#       VERSION:  v1.0
#       CREATED:  2022-08-24 01:01 UTC started.
#      REVISION:  ---
#===============================================================================
set -e

__ROOTDIR="$(pwd)"
ROOTDIR="${ROOTDIR:-$__ROOTDIR}"
DRY_RUN="${DRY_RUN-'1'}"

# DEV_ROOT_BASE can be customized. Defaults to working dir
# WARNING: the final / is very import for rsync. That's why we hardcoded here
RSYNC_LOCAL="${ROOTDIR-$DEV_ROOT_BASE}/"
__RSYNC_REMOTE_DEFAULT="urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai"
RSYNC_REMOTE="${RSYNC_REMOTE-$__RSYNC_REMOTE_DEFAULT}"

set -x
# ssh://urn.etica.ai/home/urneticaai/urn.etica.ai

# rsync -tir --chmod=ugo+rwx --delete --exclude 'configuration.php' /workspace/git/EticaAI/urn-resolver urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai
# rsync --times --itemize-changes --recursive \
#   --dry-run \
#   --chmod=ugo+rwx \
#   --delete \
#   /workspace/git/EticaAI/urn-resolver \
#   urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai

# @TODO make rsync only deal with /.well-known/urn/ and not /.well-known/
#       see https://unix.stackexchange.com/questions/34787/rsync-with-absolute-paths-and-excluding-subpaths

# rsync --times --itemize-changes --recursive --dry-run --chmod=ugo+rwx --exclude '.git' --delete  /workspace/git/EticaAI/urn-resolver urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai
# rsync --times --itemize-changes --recursive --chmod=ugo+rwx --exclude '.git' --delete  /workspace/git/EticaAI/urn-resolver/ urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai
# rsync --checksum --itemize-changes --recursive --chmod=ugo+rwx --exclude '.git' --delete /workspace/git/EticaAI/urn-resolver/ urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai

if [ "$DRY_RUN" = "" ] || [ "$DRY_RUN" = "0" ]; then
  rsync --checksum \
    --dry-run \
    --itemize-changes \
    --recursive \
    --chmod=ugo+rwx \
    --exclude '.git' \
    --delete \
    "$RSYNC_LOCAL" \
    "$RSYNC_REMOTE"
else
  rsync --checksum \
    --itemize-changes \
    --recursive \
    --chmod=ugo+rwx \
    --exclude '.git' \
    --delete \
    "$RSYNC_LOCAL" \
    "$RSYNC_REMOTE"
fi

# https://superuser.com/questions/181517/how-to-execute-a-command-whenever-a-file-changes
#     while inotifywait -e close_write lib/urnresolver.php; do ./scripts/sync-node-a.sh; sleep 5; done

# sftp://urn.etica.ai/home/urneticaai/logs/urn.etica.ai/http/error.log

# tail -f /home/urneticaai/logs/urn.etica.ai/http/error.log

set +x
