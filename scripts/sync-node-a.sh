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


# ssh://urn.etica.ai/home/urneticaai/urn.etica.ai

# rsync -tir --chmod=ugo+rwx --delete --exclude 'configuration.php' /workspace/git/EticaAI/urn-resolver urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai
# rsync --times --itemize-changes --recursive \
#   --dry-run \
#   --chmod=ugo+rwx \
#   --delete \
#   /workspace/git/EticaAI/urn-resolver \
#   urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai

# rsync --times --itemize-changes --recursive --dry-run --chmod=ugo+rwx --exclude '.git' --delete  /workspace/git/EticaAI/urn-resolver urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai
rsync --times --itemize-changes --recursive --chmod=ugo+rwx --exclude '.git' --delete  /workspace/git/EticaAI/urn-resolver/ urneticaai@urn.etica.ai:/home/urneticaai/urn.etica.ai