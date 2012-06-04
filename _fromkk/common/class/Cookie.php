<?php

/**
 * Cookieクラス
 *
 * @package atFramework
 */
class Cookie {
    /**
     * Cookieクラスのインスタンス
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Cookie設定デフォルトパス
     */
    const DEFAULT_PATH   = '/';

    //Cookie設定ドメイン
    const DEFAULT_DOMAIN = DOMAIN;

    /**
     * 初期化
     *
     * @return object
     */
    private function  __construct() {
        return $this;
    }

    /**
     * インスタンス取得
     *
     * @return object
     */
    public static function getInstance() {
        if ( true === is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Cookie設定
     *
     * @param string $name
     * @param string $value
     * @param string $expire
     */
    public function set($name, $value = null, $expire = NULL ) {
        return setcookie($name, $value, $expire, self::DEFAULT_PATH, '.' . self::DEFAULT_DOMAIN);
    }

    /**
     * Cookie取得
     *
     * @param string $name
     * @return string
     */
    public function get($name) {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } else {
            return false;
        }
    }

    /**
     * Cookie削除
     *
     * @param string $name
     * @return boolean
     */
    public function del($name) {
        if (isset($_COOKIE[$name])) {
            setcookie( $name, null, time() - 60, self::DEFAULT_PATH, '.' . self::DEFAULT_DOMAIN );
        } else {
            return false;
        }
    }
}
