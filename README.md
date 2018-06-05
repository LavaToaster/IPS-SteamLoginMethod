Steam Login Method
==================

This application for Invision Community Suite 4.3+ adds a Steam Login Method that allow your users to login with their Steam account.

## Installation Requirements:
* Minimum Requirements for Invision Community Suite(as found on https://invisioncommunity.com/files/file/7046-get-ready-for-ips-community-suite/).
    * If you are using Invison Community Cloud Hosting, this is already done for you.
* Is curl enabled? If not, install and/or enable it.

## Installation

1. Login to your ACP, and browse to System -> Site Features -> Application
2. Click Install. Upload steamlogin.tar
3. Browse to System -> Settings -> Login & Registrations
4. Within the methods tab, click Create New.
5. Click Steam, Continue, and fill in the form modifying settings as you wish.
6. It should now be ready to go. Go to your account settings to link your profile!
7. (づ｡◕‿‿◕｡)づ

## Upgrade from 4.2 Plugin

1. Login to your ACP, and browse to System -> Site Features -> Application
2. Click Install. Upload steamlogin.tar
3. Manually run the queue. (Optional, but it speeds up conversion of logins)
4. Delete the following files from your server:
    * applications/core/interface/steam/auth.php
    * applications/core/interface/steam/index.html
    * applications/core/sources/ProfileSync/Steam.php
    * applications/core/sources/ProfileSync/index.html
    * system/Login/Steam.php
5. It should be ready to go.
6. (づ｡◕‿‿◕｡)づ

## Application Notes: 
* If you are using a free hosting, then support will not be provided - If you can afford Invision Community Suite, you can afford a decent host.
* If use IIS as your web server and encounter any issues, please try switching to Apache or another server type. IIS has been known to have compatibility issues.

