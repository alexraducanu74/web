<?php
define('DIRECTOR_SITE', __DIR__);
define('SLASH', DIRECTORY_SEPARATOR);

function autoload($class)
{
  $firebasePrefix = 'Firebase\\JWT\\';
  if (strpos($class, $firebasePrefix) === 0) {
    $file = DIRECTOR_SITE . SLASH . 'vendor' . SLASH . 'firebase' . SLASH . 'php-jwt' . SLASH . 'src' . SLASH . str_replace('\\', SLASH, substr($class, strlen($firebasePrefix))) . '.php';
    if (file_exists($file)) {
      require_once $file;
      return;
    }
  }

  if (file_exists(DIRECTOR_SITE . SLASH . 'util' . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'util' . SLASH . strtolower($class) . '.php';
  else if (file_exists(DIRECTOR_SITE . SLASH . 'models' . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'models' . SLASH . strtolower($class) . '.php';
  else if (file_exists(DIRECTOR_SITE . SLASH . 'controllers' . SLASH . $class . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'controllers' . SLASH . $class . '.php';
  else if (file_exists(DIRECTOR_SITE . SLASH . 'views' . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'views' . SLASH . strtolower($class) . '.php';
  else if (file_exists(DIRECTOR_SITE . SLASH . 'config' . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'config' . SLASH . strtolower($class) . '.php';
  else if (file_exists(DIRECTOR_SITE . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . strtolower($class) . '.php';
  else {
    echo "nu gasesc clasa $class";
    exit();
  }
}
spl_autoload_register('autoload');