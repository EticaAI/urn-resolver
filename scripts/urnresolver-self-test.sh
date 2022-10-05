#!/bin/bash
#===============================================================================
#
#          FILE:  urnresolver-self-test.sh
#
#         USAGE:  ./scripts/urnresolver-self-test.sh
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
#       CREATED:  2022-10-03 01:20 UTC started.
#      REVISION:  ---
#===============================================================================
set -e

__ROOTDIR="$(pwd)"
ROOTDIR="${ROOTDIR:-$__ROOTDIR}"
__TEMPDIR="$ROOTDIR/scripts/temp"
TEMPDIR="${TEMPDIR:-$__TEMPDIR}"
__URNRESOLVER_ENTRYPOINT="http://localhost:8000/"
URNRESOLVER_ENTRYPOINT="${URNRESOLVER_ENTRYPOINT:-$__URNRESOLVER_ENTRYPOINT}"

blue=$(tput setaf 4)
red=$(tput setaf 1)
normal=$(tput sgr0)

# set -x
curl --silent "$URNRESOLVER_ENTRYPOINT/urn:resolver:exemplum?=u2709=.tsv" >"$TEMPDIR/urn:resolver:_allexamples.tsv"

# set +x

{
  # This read skip first line before the loop
  read -r
  while IFS=$'\t' read -r -a line; do

    response=$(curl -I --write-out '%{http_code}' --silent --output /dev/null "${line[1]}")

    if [ "$response" != "302" ]; then
      printf "${red}%s\t%s${normal}\n" "$response" "${line[1]}"
    else
      printf "${blue}%s\t%s${normal}\n" "$response" "${line[1]}"
    fi

    # Avoid goint too fast
    sleep 2
  done
} <"$TEMPDIR/urn:resolver:_allexamples.tsv"

set +x
