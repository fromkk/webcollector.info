<?php

    /**
     * メール送信クラス
     *
     * @package atFramework
     * @author Kazuya Ueoka
     * @copyright ATGS corp.
     */
    class SendMail
    {
        private static $instance = null;

        //SETTING
        const FROM_ADDRESS           = 'sns@c-bukatsu.com';
        const BASE_CHARSET           = 'UTF-8';

        const TEXT_MAIL_CHARSET      = 'ISO-2022-JP';
        const TEXT_MAIL_ENCODE       = '7bit';

        private function  __construct()
        {
            return $this;
        }

        public static function &getInstance()
        {
            if ( true === is_null( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public function text($to, $subject, $body, $from = self::FROM_ADDRESS, $aryAddHeader = array())
        {

            $aryHeader = array(
                'From'                      => $from
              , 'MIME-Version'              => '1.0'
              , 'Content-Type'              => "text/plain; charset=" . self::TEXT_MAIL_CHARSET
              , 'Content-Transfer-Encoding' => self::TEXT_MAIL_ENCODE
            );

            foreach ( $aryAddHeader as $field => $value ) {
                $aryHeader[$field] = $value;
            }

            $strHeader = '';
            foreach ( $aryHeader as $field => $value ) {
                if ( false === is_array($value) ) {
                    $strHeader .= $field. ': ' . $value . "\n";
                } else {
                    foreach ( $value as $add ) {
                        $strHeader .= $field. ': ' . $add . "\n";
                    }
                }
            }

            $subject = "=?iso-2022-jp?B?" . base64_encode( mb_convert_encoding( $subject , self::TEXT_MAIL_CHARSET, self::BASE_CHARSET ) ) . '?=';
            $body    = mb_convert_encoding( $body , self::TEXT_MAIL_CHARSET, self::BASE_CHARSET );

            return mail( $to, $subject, $body, $strHeader, '-f ' . $from );
        }
    }
