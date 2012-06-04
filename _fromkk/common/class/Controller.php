<?php
    multi_include(CLASS_PATH, array(
        'Plugin.php',
        'Pager.php',
        'Request.php',
        'Session.php',
        'Cookie.php',
    ));
    
    class Controller {
        protected $config;
        
        protected $assign = array();
        
        protected $model = array();
        
        /**
         * 
         * @var Plugin
         */
        protected $p;
        
        /**
         * 
         * @var Session
         */
        protected $session;
        
        /**
         * 
         * @var Cookie
         */
        protected $cookie;
        
        /**
         *
         * @var Pager
         */
        protected $pager;
        
        /**
         *
         * @var Request
         */
        private $request;
        
        /**
         *
         * @var string
         */
        protected $mode;
        
        /**
         *
         * @var string
         */
        protected $action;
        
        protected $header = 'Content-type:text/html; charset=UTF-8';


        public function __construct() {
            $this->p = new Plugin();
            $this->pager = new Pager();
            
            $this->session = Session::getInstance();
            $this->cookie  = Cookie::getInstance();
            
            $this->request = Request::getInstance();
            
            $this->mode = $this->request('mode', 'notnull');
        }
        
        public function setConfig(array $config) {
            $this->config = $config;
        }
        
        public function addConfig($name, $value) {
            $this->config[$name] = $value;
        }
        
        protected function get($name, $check = null) {
            return $this->request->get($name, $check);
        }
        
        protected function post($name, $check = null) {
            return $this->request->post($name, $check);
        }
        
        protected function request($name, $check = null) {
            return $this->request->req($name, $check);
        }
        
        protected function loadModel( $model )
        {
            $pathModel = MODEL_PATH . DS . $model . '.php';
            if ( !is_file($pathModel) ) {
                trigger_error("Model not found : {$model}", E_USER_WARNING);
            } else {
                require_once $pathModel;
                
                $modelName = ucfirst('dao_' . $model);
                $this->model[$model] = new $modelName();
                
                return $this->model[$model];
            }
        }
        
        protected function header() {
            header($this->header);
        }
        
        protected function setHeader($header) {
            $this->header = $header;
        }


        /**
         * 出力
         *
         * @param string $filename 
         */
        protected function display($filename) {
            $path = VIEW_PATH . DS . $filename;
            if (! is_file($path)) {
                trigger_error("VIEW [{$filename}] not found", E_USER_ERROR);
            }
            
            $this->header();
            
            require $path;;
        }
        
        protected function assign($name, $value = null) {
            if (1 === func_num_args() && is_array($name)) {
                $this->assign += $name;
            } else if ( 2 === func_num_args() && is_string($name) ) {
                $this->assign[$name] = $value;
            } else {
                return false;
            }
            
            return true;
        }
        
        protected function import($file) {
            $path = VIEW_PATH . DS . $file;
            
            if (! is_file($path) ) {
                trigger_error('Import errot: file not found[' + $file + ']', E_USER_WARNING);
                return false;
            }
            
            require $path;
        }
        
        protected function _($name, $feature_name = null) {
            if (! isset($this->assign[$name])) {
                trigger_error('Assign not found:' . $name, E_USER_WARNING);
                return null;
            }
            
            $result = $this->assign[$name];
            if ( null !== $feature_name ) {
                if (! method_exists($this->p, $feature_name)) {
                    trigger_error('Method not found:' . $feature_name, E_USER_WARNING);
                    return false;
                }
                
                $result = $this->p->$feature_name($result);
            }
            
            return $result;
        }
        
        protected function o($name, $feature_name = null) {
            echo $this->_($name, $feature_name);
        }
        
        protected function c($name, $feature_name = null) {
            if (! isset($this->config[$name])) {
                trigger_error('Config not found:' . $name, E_USER_WARNING);
                return null;
            }
            
            $result = $this->config[$name];
            if ( null !== $feature_name ) {
                if (! method_exists($this->p, $feature_name)) {
                    trigger_error('Method not found', E_USER_WARNING);
                    return false;
                }
                
                $result = $this->p->$feature_name($result);
            }
            
            return $result;
        }
        
        protected function oc($name, $feature_name = null) {
            echo $this->c($name, $feature_name);
        }
    }