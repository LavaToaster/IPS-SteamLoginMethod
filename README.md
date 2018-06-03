Sign in through Steam
=====================
This Plugin for IPS 4.3+ will allow your users to login with their Steam account.

## Recommended Installation Requirements:
* Minimum IP.Core Requirements ( as found on https://invisioncommunity.com/files/file/7046-get-ready-for-ips-community-suite/ )
    * If you are using Invison Power's Cloud Hosting, this is already done for you.
* Is curl enabled? If not, install/enable it please.

Notes: 
* If you are using a free hosting, then support will not be provided - If you can afford Invision Power's Software,
  you can afford a decent host.
* If use IIS as your web server and encounter any issues, please try switching to Apache or another server type. IIS is known to have compatibility issues with this plugin.

## Installation

1. First, upload the contents of the upload folder to your forum root directory.
2. Login to your ACP, and browse to System -> Plugins
3. Click Install New Plugin. Browse to the extracted zip file and upload steam_login.xml
4. Visit - https://steamcommunity.com/dev/apikey and follow the instructions to obtain an API key
5. Navigate to System -> Settings -> Login Handlers, click the edit icon on the Steam row and paste your API key in the API Key input and save
6. Make sure to enable both plugin and login method.
