<?php
    function is_hidden_file($filename) {
        if (! is_string($filename)) {
            trigger_error('filename is not string', E_USER_WARNING);
            return false;
        }
        
        return '.' === substr($filename, 0, 1);
    }
    
    /**
     *
     * @param string $dir 
     */
    function trim_dirname($dir) {
        if (! is_string($dir)) {
            trigger_error('dirname is not string', E_USER_WARNING);
            return false;
        }
        
        return preg_replace('/\/+$/', '', $dir);
    }
    
    function array_hidden_files() {
        return array('.', '..', '.DS_Store', '.localized');
    }
    
    /**
     *
     * @param type $dir
     * @param array $files 
     */
    function multi_include($dir, array $files = array()) {
        if (! is_dir($dir) ) {
            trigger_error('Directory not found', E_USER_WARNING);
            return false;
        }
        
        $isFixed      = 0 !== count($files);
        $_hiddenFiles = array_hidden_files();
        $dir          = trim_dirname($dir);
        $aryRequired  = get_required_files();
        
        $handle = opendir($dir);
        if (! $handle) {
            trigger_error('Directory open error', E_USER_WARNING);
        }
        
        $filepath = '';
        while ($filename = readdir($handle)) {
            $filepath = $dir . DS . $filename;

            if (in_array($filename, $_hiddenFiles) || is_hidden_file($filename) || in_array($filepath, $aryRequired) ) {
                continue;
            }
            
            if ($isFixed) {
                if (! in_array($filename, $files)) {
                    continue;
                }
            }

            require $filepath;
        }
    }
    