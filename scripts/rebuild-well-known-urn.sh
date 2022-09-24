#!/bin/bash
#===============================================================================
#
#          FILE:  rebuild-well-known-urn.sh
#
#         USAGE:  ./scripts/rebuild-well-known-urn.sh
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
#       CREATED:  2022-08-24 05:47 UTC started.
#      REVISION:  ---
#===============================================================================
set -e

__ROOTDIR="$(pwd)"
ROOTDIR="${ROOTDIR:-$__ROOTDIR}"
WELL_KNOWN_SOURCE="$ROOTDIR/resolvers"
WELL_KNOWN_PUBLIC="$ROOTDIR/public/.well-known/urn"

#### functions _________________________________________________________________

#######################################
# Return if a path (or a file) don't exist or if did not changed recently.
# Use case: reload functions that depend on action of older ones.
# Opposite: stale_archive
#
# Globals:
#   WELL_KNOWN_SOURCE
# Arguments:
#   path_or_file
#   maximum_time (default: 5 minutes)
# Outputs:
#   1 (if need reload, Void if no reload need)
#######################################
rebuild_well_known_urn() {

  # for entry in "$ROOTDIR/resolvers"/*.urnr.yml

  echo "# .well-known/urn/urn.txt" >"$WELL_KNOWN_PUBLIC/urn.txt"
  echo "" >>"$WELL_KNOWN_PUBLIC/urn.txt"

  # @TODO maybe already sort for long prefixes here to make easier for
  #       other parsers
  for entry in "$WELL_KNOWN_SOURCE"/*.urnr.yml; do
    source_name="${entry##*/}"
    public_name="${source_name%.yml}.json"
    # urn_prefix="${source_name%.urnr.yml}"
    echo "$source_name $public_name $entry"
    yq <"$entry" >"$WELL_KNOWN_PUBLIC/$public_name"
    # echo "$urn_prefix=$public_name" >>"$WELL_KNOWN_PUBLIC/urn.txt"
    echo "$public_name" >>"$WELL_KNOWN_PUBLIC/urn.txt"
  done
}

#### main ______________________________________________________________________

rebuild_well_known_urn
