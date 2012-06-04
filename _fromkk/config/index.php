<?php
    define('INI_PATH', APPLICATION_PATH . DS . 'config/ini');
    
    $_config = array();
    $_config = parse_ini_file_from_dir(INI_PATH);
    
    define('SITE_TITLE', ! isset($_config['site_title']) ? $_config['site_title'] : null);
    
    $scheme = 'http';
    if ( isset($_SERVER['HTTPS']) ) {
        $scheme = 'https';
    }
    define('URL'       , isset($_config['domain']) ? $scheme . '://' . $_config['domain'] : $scheme . '://' . $_SERVER['SERVER_NAME']);
    