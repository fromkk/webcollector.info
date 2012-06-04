<?php
    define('APPLICATION_PATH', CORE_PATH . DS . 'application');
    define('COMMON_PATH'     , CORE_PATH . DS . 'common');
    define('CONFIG_PATH'     , CORE_PATH . DS . 'config');
    
    define('CONTROLLER_PATH' , APPLICATION_PATH . DS . 'controller');
    define('MODEL_PATH'      , APPLICATION_PATH . DS . 'model');
    define('VIEW_PATH'       , APPLICATION_PATH . DS . 'view');
    
    define('CLASS_PATH'      , COMMON_PATH . DS . 'class');
    define('FUNCTION_PATH'   , COMMON_PATH . DS . 'function');
    
    require FUNCTION_PATH . DS . 'file.php';
    multi_include(CLASS_PATH, array(
        'Holiday.php',
        'Controller.php',
        'Dao.php',
    ));
    
    multi_include(FUNCTION_PATH, array(
        'general.php',
    ));
    
    $_config = array();
    require CONFIG_PATH . DS . 'index.php';
    
    $aryControllerInfo = makeControllerName();
    
    if (! is_file($aryControllerInfo['path'])) {
        disp404();
    }
    
    require $aryControllerInfo['path'];
    
    if (! class_exists($aryControllerInfo['class'])) {
        disp404();
    }
    
    $controller = new $aryControllerInfo['class'];
    if (! method_exists($controller, $aryControllerInfo['method'])) {
        disp404();
    }
    
    $_config['action'] = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : 'index';
    $_config['class']  = $aryControllerInfo['class'];
    $_config['method'] = $aryControllerInfo['method'];
    $_config['self']   = basename($_config['action']) . '.html';
    $controller->setConfig($_config);
    
    $controller->$aryControllerInfo['method']();