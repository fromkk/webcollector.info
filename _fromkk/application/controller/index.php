<?php
    class Index extends Controller {
        const TPL_PATH = 'index.php';
        
        public function __construct() {
            parent::__construct();
        }
        
        public function index() {
            $this->display(self::TPL_PATH);
        }
    }