<?php
    /**
     * メールテンプレート取得用クラス
     * 
     * 取得テンプレートはXML形式で
     * <?xml version="1.0" encoding="encoding"?>
     * <Root>
     *     <Subject>件名</Subject>
     *     <Body>本文</Body>
     * </Root>
     * となっている必要がある
     * 文字コードはそれぞれ設定可能
     *
     * created 2010/12/13
     * updated 2010/12/16 resetAssign作成
     * @package atFramework
     * @author Kazuya Ueoka
     * @version 0.11
     */
    class MailTemplate
    {
        /**
         * テンプレートの文字コード
         *
         * @var string
         */
        private $tplCharset   = 'UTF-8';

        /**
         * 返す文字コード
         *
         * @var string
         */
        private $getCharset   = 'UTF-8';

        /**
         * アサインされた値一覧
         *
         * @var array
         */
        private $aryAssign    = array();

        /**
         * テンプレートのディレクトリ
         *
         * @var string
         */
        private $dirTemplate  = null;

        /**
         * テンプレートのファイル名
         *
         * @var string
         */
        private $fileTemplate = null;

        /**
         * 独自タグのスタート文字
         *
         * @var string
         */
        private $tagStart     = '{$';

        /**
         * 独自タグの終了タグ
         *
         * @var string
         */
        private $tagEnd       = '}';

        /**
         * XMLオブジェクト
         *
         * @var object
         */
        private $xml          = null;

        /**
         * 件名
         *
         * @var string
         */
        private $subject      = null;

        /**
         * 本文
         *
         * @var string
         */
        private $body         = null;

        /**
         * 初期化
         *
         * @return object
         */
        public function  __construct() {
            return $this;
        }

        /**
         * テンプレートの文字コードを設定
         *
         * @param string $charset
         */
        public function setTplCharset( $charset ) {
            $this->tplCharset = $charset;
        }

        /**
         * 取得するデータの文字コードを設定
         *
         * @param string $charset
         */
        public function setGetCharset( $charset ) {
            $this->getCharset = $charset;
        }

        /**
         * テンプレート文字コードを取得
         *
         * @return string
         */
        public function getTplCharset()
        {
            return $this->tplCharset;
        }

        /**
         * 取得するデータの文字コードを取得
         *
         * @return string
         */
        public function getGetCharset()
        {
            return $this->getCharset;
        }


        /**
         * テンプレートのディレクトリパスを取得
         *
         * @return string
         */
        public function getDirTemplate()
        {
            return $this->dirTemplate;
        }

        /**
         * テンプレートのディレクトリパスを設定
         *
         * @param string $dir
         * @return boolean
         */
        public function setDirTemplate( $dir )
        {
            if ( false === is_dir( $dir ) ) {
                return false;
            }

            $this->dirTemplate = $this->_removeLastSeparator($dir, DIRECTORY_SEPARATOR);
        }

        /**
         * テンプレートのファイル名を取得
         *
         * @return string
         */
        public function getFileTemplate()
        {
            return $this->fileTemplate;
        }

        /**
         * テンプレートのファイル名を設定
         *
         * @param string $file
         * @return boolean
         */
        public function setFileTemplate( $file )
        {
            if ( false === $this->_issetDirTemplate() ) {
                trigger_error('Template Directory Does\'nt Set', E_USER_NOTICE);
                return false;
            }

            if ( false === is_file( $this->getDirTemplate() . DIRECTORY_SEPARATOR . $file ) ) {
                trigger_error('Template file not found', E_USER_ERROR);
                return false;
            }

            $this->fileTemplate = $file;

            $this->_readTemplate();
        }

        /**
         * テンプレートのパスを取得
         *
         * @return string
         */
        public function getPathTemplate()
        {
            if ( true === $this->_issetDirTemplate() && true === $this->_issetFileTemplate() ) {
                return $this->getDirTemplate() . DIRECTORY_SEPARATOR . $this->getFileTemplate();
            }

            return false;
        }

        /**
         * 置換用の配列を取得
         *
         * @return array
         */
        public function getAryAssign()
        {
            return $this->aryAssign;
        }

        /**
         * 置換用の項目を取得
         *
         * @param string $column
         * @return string
         */
        public function getAssign( $column )
        {
            return isset($this->aryAssign[$column]) ? $this->aryAssign[$column] : false;
        }

        /**
         * 項目を挿入する
         *
         * @param string $column
         * @param string $value
         */
        public function assign($column, $value = null)
        {
            if ( null === $value && true === is_array($column) && false === $this->_isNumberedArray($column) ) {
                foreach ( $column as $key => $val ) {
                    $this->aryAssign[$key] = $val;
                }
            } else if ( true === is_string( $column ) ) {
                $this->aryAssign[ $column ] = $value;
            }
        }

        /**
         * 置換情報を初期化する
         *
         * @created 2010/12/16
         */
        public function resetAssign()
        {
            $this->aryAssign = array();
        }

        /**
         * 件名を取得する
         *
         * @return string
         */
        public function subject()
        {
            return strtr( $this->subject, $this->_convertTags( $this->getAryAssign() ) );
        }

        /**
         * 本文を取得する
         *
         * @return string
         */
        public function body()
        {
            return strtr( $this->body, $this->_convertTags( $this->getAryAssign() ) );
        }

        /**
         * テンプレートファイルを読み込む
         *
         * @return string
         */
        private function _readTemplate()
        {
            if ( false !== $this->getPathTemplate() ) {
                if ( true === class_exists('DOMDocument') ) {
                    return $this->_readTemplateFromDom();
                } else if ( true === function_exists('simplexml_load_string') ) {
                    return $this->_readTemplateFromSimple();
                }
            }

            return false;
        }

        /**
         * DOMDocumentを使用して読み込む
         */
        private function _readTemplateFromDom()
        {
            $this->xml = new DOMDocument('1.0', $this->tplCharset);
            $this->xml->load( $this->getPathTemplate() );

            $this->subject = $this->xml->getElementsByTagName('Subject')->item(0)->nodeValue;
            $this->body    = $this->xml->getElementsByTagName('Body')->item(0)->nodeValue;

            $this->subject = mb_convert_encoding($this->subject, $this->getCharset, 'UTF-8' );
            $this->body    = mb_convert_encoding($this->body   , $this->getCharset, 'UTF-8');
        }

        /**
         * SimpleXMLElementを取得して読み込む
         */
        private function _readTemplateFromSimple()
        {
            $strFile = $this->_file( $this->getPathTemplate() );
            $strFile = mb_convert_encoding( $strFile, 'UTF-8', $this->tplCharset );
            $strFile = str_replace( $this->tplCharset, 'UTF-8', $strFile );

            $this->xml = simplexml_load_string( $strFile, 'SimpleXMLElement', LIBXML_NOCDATA );

            $this->subject = mb_convert_encoding( (String)$this->xml->Subject, $this->getCharset, 'UTF-8' );
            $this->body    = mb_convert_encoding( (String)$this->xml->Body   , $this->getCharset, 'UTF-8');
        }

        /**
         * $pathからファイルを読み込む
         *
         * @param string $path
         * @return string
         */
        private function _file( $path )
        {
            if ( false === is_file( $path ) ) {
                return false;
            }

            $result = '';
            $handle = fopen( $path, 'r' );

            if ( false === is_resource( $handle ) ) {
                return false;
            }

            while ( false === feof( $handle ) ) {
                $result .= fgets( $handle, 4096 );
            }

            fclose( $handle );

            return $result;
        }

        /**
         * $argsを独自タグの形式に変換する
         *
         * @param string $args
         * @return string
         */
        private function _convertTags( $args ) {
            $result = array();

            if ( true === is_array( $args ) && false === $this->_isNumberedArray($args) ) {
                foreach ( $args as $key => $val ) {
                    $result[ $this->_convertTags( $key ) ] = $val;
                }
            } else if ( true === is_string( $args ) ) {
                $result = $this->tagStart . $args . $this->tagEnd;
            } else {
                $result = $args;
            }

            return $result;
        }

        /**
         * 連番形式の配列ならtrue
         *
         * @param array $args
         * @return boolean
         */
        private function _isNumberedArray( $args )
        {
            if ( false === is_array($args) ) {
                return false;
            }

            reset( $args );
            list($key, ) = each( $args );

            return 0 === $key;
        }

        /**
         * パス情報の最後に「/」があれば削除
         *
         * @param array $args
         * @param string $separator
         * @return array
         */
        private function _removeLastSeparator( $args, $separator = DIRECTORY_SEPARATOR )
        {
            $lenSep = strlen( $separator );

            if ( true === is_array( $args ) ) {
                foreach ( $args as $key => $val ) {
                    $args[ $key ] = $this->_removeLastSeparator($val, $separator);
                }
            } else if ( true === is_string( $args ) ) {
                $lenArgs = strlen( $args );

                $args = $separator === substr( $args, 0 - $lenSep, $lenSep ) ? substr( $args , 0, $lenArgs - 1 ) : $args;
            }

            return $args;
        }

        /**
         * テンプレートディレクトリ情報が設定されていればtrue
         *
         * @return boolean
         */
        private function _issetDirTemplate()
        {
            return null !== $this->getDirTemplate();
        }

        /**
         * テンプレートファイル情報が設定されていればtrue
         *
         * @return boolean
         */
        private function _issetFileTemplate()
        {
            return null !== $this->getFileTemplate();
        }
    }