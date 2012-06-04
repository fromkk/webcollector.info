<?php

/**
 * Sessionクラス
 *
 * @author Kazuya Ueoka
 */
class Session {
    /**
     * Sessionクラスのインスタンス
     *
     * @var object
     */
    private static $instance = null;

    /**
     * 呼び出し時実行
     */
    private function  __construct() {
        if ( false === isset( $_SESSION ) ) {
            //出力制御
            if ( false === headers_sent() ) {
                session_start();
                session_regenerate_id(true);
            }
        }
    }

    /**
     * インスタンスを取得する
     *
     * @return object
     */
    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * セッションID取得
     *
     * @return string
     */
    function getSessionId() {
        return session_id();
    }

    /**
     * 値を設定
     *
     * @param string $name
     * @param string $value
     */
    function set($name, $value) {
        $_SESSION[$name] = $value;
    }

    /**
     * 値を取得
     *
     * @param string $name
     * @return string
     */
    function get($name) {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return false;
        }
    }

    /**
     * 値を削除
     *
     * @param string $name
     * @return string
     */
    function del($name) {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
            return true;
        } else {
            return false;
        }
    }
}
