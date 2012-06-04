<?php

    /**
     * GET,POST対応のバリデートクラス
     *
     * @package atFramework
     * @author Kazuya Ueoka
     * @copyright ATGS
     */
    class Request
    {
        /**
         * 複数の値を設定するデリミタ
         */
        const MULTI_VALUE_DELIMITER  = ',';

        /**
         * 文字の範囲を指定するデリミタ
         */
        const RANGE_VALUE_DELIMITER  = '-';

        /**
         * 文字の長さを指定するデリミタ
         */
        const STR_LENGTH_DELIMITER   = '_';

        /**
         * 複数の判断を指定するデリミタ
         */
        const MULTI_CHECK_DELIMITER  = '&';

        /**
         * いずれかの判断を指定するデリミタ
         */
        const EITHER_CHECK_DELIMITER = '|';

        /**
         * 最大文字数を使用する際に使用
         */
        const MAX_STR_DELIMITER      = 'max:';

        /**
         * 正規表現の値を指定するデリミタ
         */
        const REGEX_DELIMITER        = '/';

        /**
         * 内部の文字コードを指定する
         */
        const INNER_REGEX_ENCODING   = 'UTF-8';

        /**
         * Requestクラスのインスタンス
         *
         * @var object
         */
        private static $instance = null;

        /**
         * 初期化
         *
         * @return object
         */
        private function  __construct()
        {
            mb_regex_encoding(self::INNER_REGEX_ENCODING);
            
            return $this;
        }

        /**
         * インスタンスを取得する
         *
         * @return objec
         */
        public static function &getInstance()
        {
            if ( true === is_null( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * GETのnameを取得
         *
         * @param <string> $name
         * @param <string> $check
         * @return <string>
         */
        public function get( $name, $check = 'notnull' )
        {
            if (1 === func_num_args() && true === is_array(func_get_arg(0))) {
                return $this->multiCheck(func_get_arg(0), $_GET);
            } else {
                if ( false === isset( $_GET[ $name ] ) ) {
                    return false;
                }

                return $this->check( $_GET[ $name ], $check );
            }
        }

        /**
         * POSTのnameを取得
         *
         * @param <string> $name
         * @param <string> $check
         * @return <string>
         */
        public function post( $name, $check = 'notnull' )
        {
            if (1 === func_num_args() && true === is_array(func_get_arg(0))) {
                return $this->multiCheck(func_get_arg(0), $_POST);
            } else {
                if ( false === isset( $_POST[ $name ] ) ) {
                    return false;
                }

                return $this->check( $_POST[ $name ], $check );
            }
        }

        /**
         * REQUESTのnameを取得
         *
         * @param <string> $name
         * @param <string> $check
         * @return <string>
         */
        public function req( $name, $check = 'notnull' )
        {
            if (1 === func_num_args() && true === is_array(func_get_arg(0))) {
                return $this->multiCheck(func_get_arg(0), $_REQUEST);
            } else {
                if ( false === isset( $_REQUEST[ $name ] ) ) {
                    return false;
                }

                return $this->check( $_REQUEST[ $name ], $check );
            }
        }

        /**
         * バリデート実行
         *
         * @param string $value
         * @param string $type
         * @return string
         */
        public function check ( $value, $type = 'notnull' )
        {
            if ( true === is_string($value) ) {
                $value = trim($value); //add ueoka 20110330
            }

            if ( true === is_string( $type ) ) {
                if ( true === method_exists( $this, $type) ) {
                    return $this->$type( $value );
                } else if ( self::REGEX_DELIMITER === substr($type, 0, 1) && self::REGEX_DELIMITER === substr($type, -1, 1) ) {
                    return $this->regex( $value, $type );
                //OR
                } else if ( false !== strpos($type, self::EITHER_CHECK_DELIMITER) ) {
                    $aryCheck = explode( self::EITHER_CHECK_DELIMITER, $type );
                    $cntCheck = count( $aryCheck );
                    $cntError = 0;
                    for ( $i = 0; $i < $cntCheck; $i++ ) {
                        if (false === $this->check( $value, $aryCheck[$i] ) ) {
                            $cntError++;
                        }
                    }

                    return $cntError !== $cntCheck ? $value : false;
                //AND
                } else if ( false !== strpos( $type, self::MULTI_CHECK_DELIMITER ) ) {

                    $aryCheck = explode(self::MULTI_CHECK_DELIMITER, $type);
                    $cntCheck = count( $aryCheck );

                    $cntError = 0;
                    for ( $i = 0; $i < $cntCheck; $i++ ) {
                        if ( false === $this->check( $value, $aryCheck[$i] ) ) {
                            $cntError++;
                        }
                    }
                    return 0 === $cntError ? $value : false;
                //最大文字数カウント
                } else if ( false !== strpos( $type, self::MAX_STR_DELIMITER ) ) {
                    list(, $max) = explode( ':', $type );

                    return $this->max( $value, $max );
                //文字数カウント
                } else if ( false !== strpos( $type, self::STR_LENGTH_DELIMITER ) ) {

                    list($min, $max) = explode(self::STR_LENGTH_DELIMITER, $type, 2);

                    return $this->lengthRange( $value, $min, $max );
                //配列チェック
                } else if ( false !== strpos( $type, self::MULTI_VALUE_DELIMITER ) ) {
                    return $this->has( $value, explode( self::MULTI_VALUE_DELIMITER, $type ) );
                //範囲チェック
                } else if ( false !== strpos( $type, self::RANGE_VALUE_DELIMITER ) ) {
                    list( $min , $max ) = explode( self::RANGE_VALUE_DELIMITER, $type, 2 );

                    return $this->range( $value, $min, $max );
                } else if ( $type === $value ) {
                    return $value;
                } else {
                    return false;
                }
            } else if ( true === is_array( $type ) ) {
                return $this->has( $value, $type );
            }
        }

        /**
         * 配列の値をバリデート
         *
         * @param array $aryCheck
         * @param array $array
         * @return array
         */
        public function multiCheck( $aryCheck, $array )
        {
            $result = array();

            foreach ( $aryCheck as $key => $check ) {
                $result[ $key ] = isset( $array[ $key ] ) ? $this->check( $array[$key], $check ) : false;
            }

            return $result;
        }

        /**
         * 空白ならfalse
         *
         * @param string $value
         * @return string
         */
        private function notnull( $value )
        {
            return 0 !== strlen( $value ) ? $value : false;
        }

        private function null( $value ) {
            return false === $this->notnull($value) ? $value : false;
        }

        /**
         * 配列でなければfalse
         *
         * @param array $array
         * @return boolean
         */
        private function ary( $array )
        {
            return is_array( $array ) ? $array : false ;
        }

        /**
         * 半角文字でなければfalse
         *
         * @param string $value
         * @return string
         */
        private function half( $value )
        {
            return 0 !== preg_match("/^[!-~\s]+$/", $value) ? $value : false;
        }

        /**
         * ひらがなでなければfalse
         *
         * @param string$value
         * @return string
         */
        private function hiragana( $value )
        {
            if ( false !== mb_ereg_match("^([ぁ-んー　]+)$", $value) ) {
                return $value;
            }

            return false;
        }

        /**
         * カタカナでなければfalse
         *
         * @param string $value
         * @return string
         */
        private function katakana( $value )
        {
            if ( false !== mb_ereg_match("^([ァ-ヶー　]+)$", $value ) ) {
                return $value;
            }

            return false;
        }

        /**
         * 半角カナでなければfalse
         *
         * @param string $value
         * @return string
         */
        private function halfkana( $value )
        {
            if ( false !== mb_ereg_match( '^[ｦ-ﾟ]+$', $value ) ) {
                return $value;
            }

            return false;
        }

        /**
         * arrayのに値がなければfalse
         *
         * @param string $value
         * @param array $array
         * @return string
         */
        private function has( $value, $array )
        {
            if ( false === is_array( $value ) ) {
                return ( true === in_array( $value, $array ) ) ? $value : false;
            } else {
                $result = array();
                foreach ( $value as $key => $val ) {
                    if ( true === in_array( $val, $array ) ) {
                        $result[$key] = $val;
                    }
                }
                return $result;
            }
        }

        /**
         * 範囲に含まれていなければfalse
         *
         * @param integer $value
         * @param integer $min
         * @param integer $max
         * @return integer
         */
        private function range( $value, $min, $max )
        {
            return ( $min <= $value && $max >= $value ) ? $value : false;
        }

        /**
         * マルチバイトに対応した文字数カウント($max以内ならそのまま返す)
         *
         * @param string $value
         * @param integer $max
         * @return string|bool
         */
        private function max( $value, $max )
        {
            return $max >= mb_strlen($value, self::INNER_REGEX_ENCODING) ? $value : false;
        }

        /**
         * 数値でなければfalse
         *
         * @param integer $value
         * @return integer
         */
        private function num( $value )
        {
            return is_numeric( $value ) ? $value : false;
        }

        /**
         * 全角数字
         *
         * @param string $value
         * @return string
         */
        private function bignum( $value )
        {
            if (0 !== mb_ereg_match('^[０-９]+$', $value) ) {
                return $value;
            }

            return false;
        }

        /**
         * 全角アルファベット
         *
         * @param string $value
         * @return string
         */
        private function bigalp( $value )
        {
            if ( 0 !== mb_ereg_match('^[Ａ-z]$', $value) ) {
                return $value;
            }

            return false;
        }

        /**
         * 全角英数字
         *
         * @param string $value
         * @return string
         */
        private function bigalnum($value) {
            if ( 0 !== mb_ereg_match('^[Ａ-ｚ|０-９]+$', $value) ) {
                return $value;
            }

            return false;
        }

        /**
         * 厳格な数字チェック
         *
         * @param integer $value
         * @return integer
         */
        private function digit( $value )
        {
            return ctype_digit((String)$value) ? $value : false;
        }

        /**
         * 半角英数字でなければfalse
         *
         * @param string $value
         * @return string
         */
        private function alnum( $value )
        {
            return ctype_alnum( $value ) ? $value : false;
        }

        /**
         * 半角英字でなければfalse
         *
         * @param string $value
         * @return string
         */
        private function alp( $value )
        {
            return ctype_alpha( $value ) ? $value : false;
        }

        /**
         * 文字数の範囲内でなければfalse
         *
         * @param string $value
         * @param integer $min
         * @param integer $max
         * @return integer
         */
        private function lengthRange($value, $min, $max)
        {
            $cntStrLength = strlen( $value );

            return $min <= $cntStrLength && $max >= $cntStrLength ? $value : false;
        }

        /**
         * 日付でなければfalse(dateStringのラッパー)
         *
         * @param string $value
         * @return string
         */
        private function date( $value )
        {
            return $this->dateString( $value );
        }

        /**
         * 日付でなければfalse
         *
         * @param string $value
         * @return string
         */
        private function dateString( $value )
        {
            if ( false !== strpos( $value, '-' ) && false !== strpos( $value, ':' ) ) {
                list( $year, $month, $day, $hour, $minute, $second ) = sscanf( $value, '%04d-%02d-%02d %02d:%02d:%02d' );
            } else if ( false !== strpos( $value, '-' ) ) {
                list( $year, $month, $day ) = sscanf( $value, '%04d-%02d-%02d' );
            } else if ( false !== strpos( $value, '/' )  && false !== strpos( ':', $value ) ) {
                list( $year, $month, $day, $hour, $minute, $second ) = sscanf( $value, '%04d/%02d/%02d %02d:%02d:%02d' );
            } else if ( false !== strpos( $value, '/' ) ) {
                list( $year, $month, $day ) = sscanf( $value, '%04d/%02d/%02d' );
            } else {
                return false;
            }

            return true === checkdate($month, $day, $year) ? $value : false;
        }

        /**
         * 年でなければfalse(numのラッパー)
         *
         * @param integer $value
         * @return integer
         */
        private function year( $value )
        {
            return $this->num( $value );
        }

        /**
         * 月でなければfalse
         *
         * @param integer $value
         * @return integer
         */
        private function month( $value )
        {
            return $this->range( $value, 1, 12 );
        }

        /**
         * 日でなければfalse
         *
         * @param integer $value
         * @return integer
         */
        private function day( $value )
        {
            return $this->range( $value, 1, 31 );
        }

        /**
         * メールアドレスでなければfalse
         *
         * @param string $value
         * @return string
         */
        private function mail( $value )
        {
            if (0 !== preg_match( "/^[a-zA-Z0-9\-_\.!\"#\$%&'\(\)\=\^\|\[\]\{\}\?<>\+]+@([a-zA-Z0-9\-_\.]+\.[a-zA-Z0-9\-_\.]+)$/", $value, $result ) ) {
                /*
                $domain = $result[1];

                $arySmtps = array();

                $result = getmxrr( $domain, $arySmtps );

                if ( 1 === count($arySmtps) && $domain !== $arySmtps[0] ) {
                    return false;
                }

                return true === $result ? $value : false;
                 */

                return $value;
            } else {
                return false;
            }
        }

        /**
         * URLでなければfalse
         *
         * @param string $value
         * @return string
         */
        private function url( $value )
        {
            if ( 0 !== preg_match("/(?:https?|ftp):\/\/[a-zA-Z0-9\-_\.\?&=%~]+/", $value) ) {
                return $value;
            } else {
                return false;
            }
        }

        /**
         * 電話番号でなければfalse
         *
         * @param string $value
         * @return string
         */
        private function tel( $value )
        {
            if ( 0 !== preg_match("/^([0-9]+)\-([0-9]+)\-([0-9]+)$/", $value, $result) ) {

                $telNum = '';
                for ($i = 1; $i <= 3; $i++) {
                    $telNum .= $result[$i];
                }

                $totalCnt = strlen($telNum);

                return (10 <= $totalCnt && 11 >= $totalCnt) ? $value : false;
            } else {
                return false;
            }
        }

        /**
         * 郵便番号でなければfalse
         *
         * @param string $value
         * @return string
         */
        private function zipcode($value)
        {
            if ( 0 !== preg_match("/^[0-9]{3}\-[0-9]{4}$|^[0-9]{7}$/", $value) ) {
                return $value;
            } else {
                return false;
            }
        }

        /**
         * 正規表現が通らなければfalse
         *
         * @param string $value
         * @param string $regex
         * @return string
         */
        private function regex( $value, $regex )
        {
            if ( 0 !== preg_match( $regex, $value ) ) {
                return $value;
            } else {
                return false;
            }
        }
    }