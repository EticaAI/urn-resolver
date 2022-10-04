#!/bin/bash
#===============================================================================
#
#          FILE:  which-open-data-portal.sh
#
#         USAGE:  which-open-data-portal.sh http://example.org
#                 which-open-data-portal.sh https://dados.gov.br/
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

# This is just a draft script to discover what data portal one URL may have
# @TODO maybe write this in python instead of plain bash

# ./scripts/fn/which-open-data-portal.sh https://dados.gov.br/
# ./scripts/fn/which-open-data-portal.sh https://data.humdata.org/
# ./scripts/fn/which-open-data-portal.sh https://data-avl.opendata.arcgis.com/

# echo "$0"
# echo "$1"
# echo "$1/api/action/package_list"
BASE_URL="$1"
CKAN_PACKAGELIST="$BASE_URL/api/action/package_list"
ARCGIS_DATAJSON="$BASE_URL/data.json"

# https://data.humdata.org/api/action/package_list

if curl --head --silent --fail "$CKAN_PACKAGELIST" 2> /dev/null;
 then
  echo "$CKAN_PACKAGELIST This page exists. Maybe CKAN"
 else
  echo "$CKAN_PACKAGELIST This page does not exist. CKAN test failed."
fi

# Weird. Some CKAN portals return 404 even if result is okay. We may need some
# manual checks


sleep 3

if curl --head --silent --fail "$ARCGIS_DATAJSON" 2> /dev/null;
 then
  echo "$ARCGIS_DATAJSON This page exists. Maybe ArcGIS"
 else
  echo "$ARCGIS_DATAJSON This page does not exist."
fi


# https://json-ld.org/playground/#startTab=tab-table&json-ld=https%3A%2F%2Fdata-avl.opendata.arcgis.com%2F%2Fdata.json&frame=%7B%7D&context=%7B%7D


#### APIS for several data portals _____________________________________________
# Temporary notes
# @see https://dev.socrata.com/docs/formats/geojson.html
# https://en.wikipedia.org/wiki/Open_Data_Protocol
