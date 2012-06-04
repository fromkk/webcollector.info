<?php
    class MailAnalytics {

        const MAIL_NEW_LINE_TYPE_CRLF = "\r\n";

        const MAIL_NEW_LINE_TYPE_LF   = "\n";

        const MAIL_NEW_LINE_TYPE_CR   = "\r";

        const MAIL_TYPE_PLAIN         = 0;

        const MAIL_TYPE_HTML          = 1;

        const MAIL_TYPE_MIX           = 2;

        const ANALYTICS_CHARSET       = 'UTF-8';

        private $rawMail
              , $header
              , $body
              , $from
              , $to
              , $subject
              , $text
              , $html
              , $aryAttachedFile = array()
              , $aryContentFile  = array()
              , $contentType
              , $newLineType
              , $sendTime
              , $boundary
              , $charset;

        function  __construct() {
            return $this;
        }

        public function rawMail() {
            return $this->rawMail;
        }

        public function from() {
            return $this->from;
        }

        public function to() {
            return $this->to;
        }

        public function subject() {
            return $this->subject;
        }

        public function text() {
            return $this->text;
        }

        public function html() {
            return $this->html;
        }

        public function hasAttachedFile() {
            return true === is_array( $this->aryAttachedFile ) && 0 !== count( $this->aryAttachedFile ) ? true : false;
        }

        public function hasContentFile() {
            return true === is_array( $this->aryContentFile ) && 0 !== count( $this->aryContentFile ) ? true : false;
        }

        public function attachedFile() {
            return $this->aryAttachedFile;
        }

        public function contentFile() {
            return $this->aryContentFile;
        }

        public function analyticsFromPath( $path ) {
            $this->setMailFromString( $this->getFileFromPath( $path ) );

            $this->analytics();
        }

        public function analyticsFromString( $string ) {
            $this->setMailFromString( $string );

            $this->analytics();
        }

        public function analytics() {
            $this->checkNewLineType( $this->rawMail );

            list( $this->header, $this->body ) = $this->explodeDoubleNewLine( $this->rawMail );

            $arySetting = $this->arySettingFromHeader( trim( $this->header ) );

            if ( 0 !== count($arySetting) && 0 !== strlen( $this->body ) ) {
                $this->analyticsFromSettingBody( $arySetting, $this->body );
            }
        }

        private function arySettingFromHeader( $header ) {
            $aryHeader = explode( $this->newLineType, $header );
            $cntHeader = count( $aryHeader );

            $arySetting = array();

            for ( $i = 0; $i < $cntHeader; $i++ ) {
                $currentHeader = trim( $aryHeader[ $i ] );

                if ( ';' === substr( $currentHeader , -1, 1 ) ) {
                    $i++;
                    $currentHeader .= ' '. trim( $aryHeader[ $i ] );
                }

                if ( false === strpos( $currentHeader, ':' ) ) {
                    continue;
                }

                list( $name, $value ) = explode( ':', $currentHeader, 2 );
                $name  = trim( $name );
                $value = trim( $value );

                if ( 'from' === strtolower( $name ) ) {
                    $from = $this->getMailAddressFromString( $value );

                    if ( 0 === strlen( $this->from ) ) {
                        $this->from = $from;
                    }

                    $arySetting[ 'from' ] = $from;
                }

                if ( 'to' === strtolower( $name ) ) {
                    $to = $this->getMailAddressFromString( $value );

                    if ( 0 === strlen( $this->to ) ) {
                        $this->to = $to;
                    }

                    $arySetting[ 'to' ] = $to;
                }

                if ( 'subject' === strtolower( $name ) ) {
                    $subject = $this->convertFromMailText( trim( $value ), self::ANALYTICS_CHARSET );

                    if ( 0 === strlen( $this->subject ) ) {
                        $this->subject = $subject;
                    }

                    $arySetting[ 'subject' ] = $subject;
                }

                if ( 'date' === strtolower( $name ) ) {
                    $date = strtotime( trim( $value ) );

                    if ( 0 === strlen( $this->sendTime ) ) {
                        $this->sendTime = $date;
                    }

                    $arySetting[ 'date' ] = $date;
                }

                if ( 'content-type' === strtolower( $name ) ) {
                    list( $content_type, $option ) = explode( ';', trim( $value ), 2 );

                    if ( 0 === strlen( $this->contentType ) ) {
                        $this->contentType = $content_type;
                    }

                    $option = trim( $option );

                    switch( strtolower( $content_type ) ) {
                        case 'text/plain':
                            
                            $charset = "ISO-2022-JP";
                            if ( 0 !== preg_match("/charset=[\"]?(.*?)[\"]?$/i", $option, $match) ) {
                                $charset = $match[1];
                            }

                            $arySetting[ 'content-type' ] = $content_type;
                            $arySetting[ 'charset' ]      = $charset;

                            if ( 0 === strlen( $this->charset ) ) {
                                $this->charset = $charset;
                            }
                            break;
                        case 'text/html':
                            $charset = $this->getPairTag( '"', '"', $option );

                            $arySetting[ 'content-type' ] = $content_type;
                            $arySetting[ 'charset' ]      = $charset;

                            if ( 0 === strlen( $this->charset ) ) {
                                $this->charset = $charset;
                            }
                            break;
                        case 'image/gif':
                        case 'image/jpeg':
                        case 'image/png':

                            $name = $this->getPairTag( '"', '"', $option );

                            $arySetting[ 'content-type' ] = $content_type;
                            $arySetting[ 'name' ]         = $name;

                            break;
                        default:
                            $boundary = $this->getPairTag( '"', '"', $option );

                            $arySetting[ 'content-type' ] = $content_type;
                            $arySetting[ 'boundary' ]     = $boundary;

                            if ( 0 === strlen( $this->boundary ) ) {
                                $this->boundary = $boundary;
                            }

                            break;
                    }
                }

                if ( 'content-transfer-encoding' === strtolower( $name ) ) {
                    $arySetting[ 'content-transfer-encoding' ] = trim( $value );
                }

                if ( 'content-disposition' === strtolower( $name ) ) {
                    list( $content_disposition, $option ) = explode( ';', $value );

                    $arySetting[ 'content-disposition' ] = trim( $content_disposition );
                    $arySetting[ 'filename' ]            = $this->getPairTag('"', '"', $option);
                }

                if ( 'content-id' === strtolower( $name ) ) {
                    $arySetting[ 'content-id' ] = trim( $value );
                }
            }

            return $arySetting;
        }

        private function analyticsFromSettingBody( $arySetting, $body ) {

            if ( true === isset( $arySetting[ 'content-type' ] ) ) {
                switch( $arySetting[ 'content-type' ] ) {
                    case 'text/plain':
                        $text = '';
                        if ( '7bit' === $arySetting[ 'content-transfer-encoding' ] ) {
                            $text = mb_convert_encoding( $body, self::ANALYTICS_CHARSET, $arySetting[ 'charset' ] );
                        } else if ( 'base64' === $arySetting[ 'content-transfer-encoding' ] ) {
                            $text = mb_convert_encoding( base64_decode( $body ), self::ANALYTICS_CHARSET, $arySetting[ 'charset' ] );
                        } else if ( 'quoted-printable' === $arySetting[ 'content-transfer-encoding' ] ) {
                            $text = mb_convert_encoding( $body, 'ISO-2022-JP', 'quoted-printable' );
                            $text = mb_convert_encoding( $text, self::ANALYTICS_CHARSET, 'ISO-2022-JP' );
                        }
                        $this->text = $text;
                        break;
                    case 'text/html':
                        $html = '';
                        if ( '7bit' === $arySetting[ 'content-transfer-encoding' ] ) {
                            $html = mb_convert_encoding( $body, self::ANALYTICS_CHARSET, $arySetting[ 'charset' ] );
                        } else if ( 'base64' === $arySetting[ 'content-transfer-encoding' ] ) {
                            $html = mb_convert_encoding( base64_decode( $body ), self::ANALYTICS_CHARSET, $arySetting[ 'charset' ] );
                        } else if ( 'quoted-printable' === $arySetting[ 'content-transfer-encoding' ] ) {
                            $aryLine = explode( $this->newLineType, trim( $body ) );

                            $html = mb_convert_encoding( implode( '', $aryLine ), 'ISO-2022-JP', 'quoted-printable' );
                            $html = mb_convert_encoding( $html, self::ANALYTICS_CHARSET, 'ISO-2022-JP' );
                        }
                        $this->html = $html;
                        break;
                    case 'image/gif':
                    case 'image/jpeg':
                    case 'image/png':

                        if ( 'base64' === $arySetting['content-transfer-encoding'] ) {
                            $aryLine = explode( $this->newLineType, trim( $body ) );
                            $image   = base64_decode( implode( '', $aryLine ) );
                        }

                        if ( true === isset( $arySetting[ 'content-id' ] ) ) {
                            $this->aryContentFile[ $arySetting['content-id'] ]  = array( $arySetting['name'] =>  $image );
                        } else if ( 'attachment' === $arySetting[ 'content-disposition' ] ) {
                            $this->aryAttachedFile[ $arySetting['name'] ]       = $image;
                        }

                        break;
                    default:

                        if ( false === strpos( $body, $arySetting[ 'boundary' ] ) ) {
                            break;
                        }

                        $aryContent = explode( $arySetting[ 'boundary' ], $body );
                        $cntContent = count( $aryContent );

                        for ( $i = 0; $i < $cntContent; $i++ ) {
                            $currentContent = substr( trim( $aryContent[ $i ] ), 0, -2 );

                            if ( 0 === strlen( $currentContent ) ) {
                                continue;
                            }
                            
                            list( $header, $content ) = $this->explodeDoubleNewLine( $currentContent );
                            $aryContentSetting = $this->arySettingFromHeader( $header );

                            if ( 0 !== count( $aryContentSetting ) && 0 !== strlen( $content ) ) {
                                $this->analyticsFromSettingBody($aryContentSetting, $content );
                            }
                        }

                        break;
                }
            }
        }

        private function checkNewLineType ( $string ) {
            if ( false !== strpos( $string, "\r\n" ) ) {
                 $this->newLineType = self::MAIL_NEW_LINE_TYPE_CRLF;
             } else if ( false !== strpos( $string, "\n" ) ) {
                 $this->newLineType = self::MAIL_NEW_LINE_TYPE_LF;
             } else if ( false !== strpos( $string, "\r" ) ) {
                 $this->newLineType = self::MAIL_NEW_LINE_TYPE_CR;
             }

             return $this->newLineType;
        }

        private function getFileFromPath( $path ) {

            $file = '';

            $rsPath = fopen( $path, 'r' );
            if ( true === is_resource( $rsPath ) ) {
                while ( false === feof( $rsPath ) ) {
                    $file .= fgets( $rsPath, 4096 );
                }
                fclose( $rsPath );

                return $file;
            } else {
                return false;
            }
        }

        public function setMailFromString( $string ) {
            $this->rawMail = $string;
        }

        private function explodeDoubleNewLine( $string ) {
            if ( 0 === strlen( $this->newLineType ) ) {
                return false;
            }

            return explode( $this->newLineType . $this->newLineType, $string, 2 );
        }

        private function getMailAddressFromString( $string ) {
            if ( 0 !== preg_match( "/([a-zA-Z0-9\-_\.]+@{1}[a-zA-Z0-9\-_\.]+)/", $string, $match ) ) {
                return $match[1];
            } else {
                return false;
            }
        }

        //メール形式のテキストをエンコード
        private function convertFromMailText( $string , $encode = null ) {
            $result = '';

            if ( 0 !== preg_match( "/=\?iso\-2022\-jp\?B\?(.*?)\?=/i", $string, $match ) ) {
                $result = mb_convert_encoding( base64_decode( $match[1] ) , true === is_null( $encode ) ? self::ANALYTICS_CHARSET : $encode, 'ISO-2022-JP' );

                $result = str_replace( $match[0], $result, $string );
            } else if ( 0 !== preg_match( "/=\?utf\-8\?B\?(.*?)\?=/i", $string, $match ) ) {
                $result = mb_convert_encoding( base64_decode( $match[1] ) , true === is_null( $encode ) ? self::ANALYTICS_CHARSET : $encode, 'UTF-8' );

                $result = str_replace( $match[0], $result, $string );
            } else {
                $result = $string;
            }

            return $result;
        }

        //stringからstart_tagとend_tagの内容を取得
        private function getPairTag( $start_tag, $end_tag, $string ) {
            $start_cnt = strpos( $string, $start_tag );
            $end_cnt   = strpos( $string, $end_tag, $start_cnt );

            if (false === $start_cnt || false === $end_cnt) {
                return $string;
            }

            return substr( $string, $start_cnt + strlen( $start_tag ), $end_cnt - ( $start_cnt + strlen( $start_tag ) ) );
        }
    }