<?php
    define('DEBUG_MODE', true);
    define('DS'        , DIRECTORY_SEPARATOR);
    
    define('CORE_NAME' , '_fromkk');
    define('CORE_PATH' , dirname(__FILE__) . DS . CORE_NAME);
    
    define('EXE_NAME'  , 'init.php');
    
    if ( DEBUG_MODE ) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        ini_set('display_errors', 0);
    }
    
    require CORE_PATH . DS . EXE_NAME;