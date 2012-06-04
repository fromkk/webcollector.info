<?php
    define('ESCAPE_MODE_TAG' , 1);
    define('ESCAPE_MODE_NL'  , 2);
    define('ESCAPE_MODE_LIKE', 4);
    define('ESCAPE_MODE_ALL' , ESCAPE_MODE_TAG | ESCAPE_MODE_NL);

    class Plugin {
        
        /**
         * ラッパー
         * 
         * @param type $value
         * @param type $start
         * @param type $end
         * @return type 
         */
        public function w($value, $start, $end) {
            return $start . $value . $end;
        }
        
        /**
         * エスケープ
         *
         * @param string $value
         * @param int $option
         * @return string
         */
        public function e($value, $option = ESCAPE_MODE_ALL) {
            if ( 0 !== $option & ESCAPE_MODE_TAG ) {
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            
            if ( 0 !== $option & ESCAPE_MODE_NL ) {
                $value = nl2br($value, true);
            }
            
            if ( 0 !== $option & ESCAPE_MODE_LIKE ) {
                $value = str_replace('\\', '\\\\', $value);
                $value = str_replace('%', '\\%', $value);
                $value = str_replace('_', '\\_', $value);
            }
            
            return $value;
        }
        
        public function is_null($value) {
            return 0 === count($value);
        }
        
        public function which($base, $true = null, $false = null) {
            return false === $this->is_null($base) ? (null === $true ? $base : $true) : $false;
        }
    }
