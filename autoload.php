<?php
define('DIRECTOR_SITE', __DIR__);
define('SLASH', DIRECTORY_SEPARATOR);

function autoload($class){
  if(file_exists(DIRECTOR_SITE . SLASH . 'util' . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'util' . SLASH . strtolower($class) . '.php';
  else if(file_exists(DIRECTOR_SITE . SLASH . 'models' . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'models' . SLASH . strtolower($class) . '.php';
  else if(file_exists(DIRECTOR_SITE . SLASH . 'controllers' . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'controllers' . SLASH . strtolower($class) . '.php';
  else if(file_exists(DIRECTOR_SITE . SLASH . 'views' . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . 'views' . SLASH . strtolower($class) . '.php';
  else if(file_exists(DIRECTOR_SITE . SLASH . strtolower($class) . '.php'))
    require_once DIRECTOR_SITE . SLASH . strtolower($class) . '.php';
  else{
      echo "nu gasesc clasa $class";
      exit();
  }
}
spl_autoload_register('autoload');