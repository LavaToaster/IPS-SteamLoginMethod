#!/usr/bin/env bash
# Example environment variables
#
# IPS_FOLDER="../IPB/"
# IPS_PLUGINKEY="steamlogin"
#
# Example command:
#
# IPS_FOLDER="../IPB/" IPS_PLUGINKEY="steamlogin" sh build.sh
#

echo "Creating Directories..."
mkdir -p ./upload/applications/core/interface/steam
mkdir -p ./upload/applications/core/sources/ProfileSync
mkdir -p ./upload/system/Login

echo "Copying Files..."
cp ${IPS_FOLDER}/applications/core/interface/steam/auth.php upload/applications/core/interface/steam
cp ${IPS_FOLDER}/applications/core/sources/ProfileSync/Steam.php upload/applications/core/sources/ProfileSync
cp ${IPS_FOLDER}/system/Login/Steam.php upload/system/Login

echo "Copying Dev Files..."
cp -r ${IPS_FOLDER}/plugins/${IPS_PLUGINKEY}/dev .

echo "Done"
