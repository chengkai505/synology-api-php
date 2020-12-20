# Synology-API-PHP-Implementation

Call various Synology Nas api in PHP.

Usage: just include main.php

## Auth

Login & Logout

```PHP
$nas = new \KAI_WU\SynoNas('Address', 'Port');
$nas->login('Username', 'Password', 'Session Name');
$nas->logout();
```

Session Name is known to be:
- FileStation
- DownloadStation
