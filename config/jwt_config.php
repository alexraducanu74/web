<?php
// File: config/jwt_config.php

define('JWT_SECRET_KEY', 'YOUR_REALLY_STRONG_AND_SECRET_KEY_GOES_HERE');
define('JWT_ISSUER', 'http://yourwebsite.com'); // The issuer of the token (your domain)
define('JWT_AUDIENCE', 'http://yourwebsite.com'); // The audience of the token (your domain)
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME_SECONDS', 3600); // Token valid for 1 hour (3600 seconds)

?>