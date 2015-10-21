Sign in through Steam
=====================
This Plugin for IPS 4.0+ will allow your users to login with their Steam account.

##Recommended Installation Requirements:
* Minimum IP.Core Requirements ( as found on https://community.invisionpower.com/files/file/7046-get-ready-for-ips-40/ )
 - If you are using InvisonPower's Hosted forums, this is already done for you.
* Is curl enabled? If not, install/enable it please.
* Hosting with Free services will not be supported
 - If you are having issues, and use IIS as your web server. Please try switching to Apache or another server type. IIS is known to have random issues.

## Installation

1. First, upload the contents of the upload folder to your forum root directory.
2. Login to your ACP, and browse to System -> Plugins
3. Click Install New Plugin. Browse to the extracted zip file and upload steam_login.xml
4. Visit - http://steamcommunity.com/dev/apikey and follow the instructions to obtain an API key
5. Navigate to System -> Login Methods, click the edit icon on the Steam row and paste your API key in the API Key input and save
6. Make sure to enable both plugin and login method.
