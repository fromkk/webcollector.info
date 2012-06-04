<?php
    /**
     * ページャークラス
     *
     * @package atFramework
     * @author Kazuya Ueoka
     * @version 1.0
     */
    class Pager
    {
        //PAGE SETTING
        private $total       = 0;  //カラムの合計値
        private $currentPage = 1;  //現在のページ
        private $limit       = 10; //表示件数
        private $range       = 9;  //pagerListのページ表示数

        //QUERY SETTING
        private $field       = 'page';  //クエリーのページ用項目名
        private $fileName    = null;    //Aタグのリンクに表示させるファイル名
        private $aryQuery    = array(); //配列のクエリー

        //HTML SETTING
        private $useTag      = null; //使用するタグ名
        private $className   = null; //タグに使用するクラス名

        public function  __construct()
        {
            $this->setAryQuery( $this->_getAryQuery() );
        }

        /**
         * $totalのセッター
         *
         * @param integer $total
         * @return integer
         */
        public function setTotal( $total )
        {
            if (false === is_integer($total) && false === ctype_digit($total) ) {
                return false;
            }

            $this->total = (int)$total;
        }

        /**
         * $totalのゲッター
         *
         * @return integer
         */
        public function getTotal()
        {
            return $this->total;
        }

        /**
         * $currentPageのセッター
         *
         * @param integer $currentPage
         * @return boolean
         */
        public function setCurrentPage( $currentPage )
        {
            if (false === is_integer($currentPage) && false === ctype_digit($currentPage) ) {
                return false;
            }

            $this->currentPage = (int)$currentPage;
        }

        /**
         * $currentPageのゲッター
         *
         * @return integer
         */
        public function getCurrentPage()
        {
            return $this->currentPage;
        }

        /**
         * $limitのセッター
         *
         * @param integer $limit
         * @return boolean
         */
        public function setLimit( $limit )
        {
            if (false === is_integer($limit) && false === ctype_digit($limit) ) {
                return false;
            }

            $this->limit = (int)$limit;
        }

        /**
         * $limitのゲッター
         *
         * @param integer $limit
         * @return integer
         */
        public function getLimit()
        {
            return $this->limit;
        }

        /**
         * $fieldのセッター
         *
         * @param string $field
         */
        public function setField( $field )
        {
            $this->field = $field;
        }

        /**
         * $fieldのゲッター
         *
         * @return string
         */
        public function getField()
        {
            return $this->field;
        }

        /**
         * $fileNameのセッター
         *
         * @param string $fileName
         */
        public function setFileName( $fileName )
        {
            $this->fileName = $fileName;
        }

        /**
         * $fileNameのセッター
         *
         * @return string
         */
        public function getFileName()
        {
            return $this->fileName;
        }

        /**
         * $aryQueryのセッター
         *
         * @param array $aryQuery
         */
        public function setAryQuery( $aryQuery )
        {
            $this->aryQuery = $aryQuery;
        }

        /**
         * $aryQueryのゲッター
         *
         * @return array
         */
        public function getAryQuery()
        {
            return $this->aryQuery;
        }

        /**
         * $rangeのセッター
         *
         * @param integer $range
         */
        public function setRange( $range )
        {
            $this->range = $range;
        }

        /**
         * $rangeのゲッター
         *
         * @return integer
         */
        public function getRange()
        {
            return $this->range;
        }

        /**
         * $useTagのセッター
         *
         * @param boolean $useTag
         */
        public function setUseTag( $useTag )
        {
            $this->useTag = $useTag;
        }

        /**
         * $useTagのゲッター
         *
         * @return boolean
         */
        public function getUseTag()
        {
            return $this->useTag;
        }

        /**
         * $classNameのセッター
         *
         * @param string $className
         */
        public function setClassName( $className )
        {
            $this->className = $className;
        }

        /**
         * $classNameのゲッター
         *
         * @return string
         */
        public function getClassName()
        {
            return $this->className;
        }

        /**
         * 現在のURL（$fileName）を取得する
         *
         * @param string $page
         * @return string
         */
        private function _getCurrentUrl( $page = false )
        {
            $fileName = $this->getFileName();
            $page     = false === $page ? $this->getCurrentPage() : $page;

            if ( null === $fileName && false === isset($_SERVER['PHP_SELF']) ) {
                return false;
            } else if ( null === $fileName ) {
                $fileName = preg_replace( '/\?.*?$/', '', basename($_SERVER['PHP_SELF']) );
            }

            $aryQuery = $this->getAryQuery();

            if ( false !== $page && null !== $this->getField() ) {
                $aryQuery[ $this->getField() ] = $page;
            }

            $strQuery = null;
            foreach ( $aryQuery as $key => $val ) {
                if ( null !== $strQuery ) {
                    $strQuery .= '&' .  sprintf( '%s=%s', $key, $val );
                } else {
                    $strQuery .= sprintf( '%s=%s', $key, $val );
                }
            }

            if ( null !== $strQuery ) {
                return sprintf( '%s?%s', $fileName, $strQuery );
            } else {
                return $fileName;
            }
        }

        /**
         * クエリーの配列を取得する
         * ※.htaccess等で$_GETとは異なることがある場合の為
         *
         * @return array
         */
        private function _getAryQuery()
        {
            $path = $_SERVER['REQUEST_URI'];

            if ( false === strpos( $path, '?' ) ) {
                return false;
            }

            list(, $strQuery) = explode( '?', $path, 2 );

            if ( 0 === strlen( $strQuery ) ) {
                return array();
            }

            $aryExpQuery = explode( '&', $strQuery );
            $cntExpQuery = count( $aryExpQuery );

            $aryQuery = array();
            for ( $i = 0; $i < $cntExpQuery; $i++ ) {
                $currentQuery = $aryExpQuery[ $i ];
                list( $field, $value ) = sscanf( $currentQuery, '%[^=]=%s' );

                $aryQuery[ $field ] = $value;
            }

            return $aryQuery;
        }

        /**
         * ページャー用のパラメーターを取得
         *
         * @return array
         */
        public function getAryParameter()
        {
            $totalPage   = (int)ceil( $this->getTotal() / $this->getLimit() );
            $currentPage = $this->getCurrentPage() > $totalPage ? $totalPage : $this->getCurrentPage();

            $start     = null;
            $end       = null;
            $halfRange = (int)ceil($this->getRange() / 2);

            if ( $currentPage <= $halfRange ) {
                $start = 1;
                $end   = $this->getRange() > $totalPage ? $totalPage : $this->getRange();
            } else if ( $currentPage > $totalPage - $halfRange ) {
                $start = 0 >= $totalPage - $this->getRange() + 1 ? 1 : $totalPage - $this->getRange() + 1;
                $end   = $totalPage;
            } else {
                $start = $currentPage - $halfRange + 1;
                $end   = $currentPage + $halfRange - 1;
            }

            return array(
                'total_page'   => $totalPage
              , 'total_count'  => $this->getTotal()
              , 'from'         => 0 === $totalPage ? 0 : ($currentPage - 1) * $this->getLimit() + 1
              , 'to'           => $currentPage === $totalPage ? $this->getTotal() : $currentPage * $this->getLimit()
              , 'start'        => $start
              , 'end'          => $end
              , 'prev'         => 0 !== $currentPage - 1 ? $currentPage - 1 : null
              , 'next'         => $totalPage >= $currentPage + 1 ? $currentPage + 1 : null
              , 'current_page' => $currentPage
              , 'offset'       => ($currentPage - 1) * $this->getLimit()
              , 'limit'        => $this->getLimit()
            );
        }

        /**
         * 前、後ろ用のページャー
         *
         * @param string $strPrev
         * @param string $strNext
         * @return string
         */
        public function pagerPrevNext( $strPrev = '&lt;&lt;', $strNext = '&gt;&gt;' )
        {
            $aryParameter = $this->getAryParameter();

            $formatTag = $this->_formatTag();

            $pagerPrev = null;
            $pagerNext = null;

            if ( null !== $aryParameter['prev'] ) {
                $anchorPrev = sprintf( '<a href="%s">%s</a>', $this->_getCurrentUrl( $aryParameter['prev'] ), $strPrev );
            } else {
                $anchorPrev = $strPrev;
            }

            if ( null !== $aryParameter['next'] ) {
                $anchorNext = sprintf( '<a href="%s">%s</a>', $this->_getCurrentUrl( $aryParameter['next'] ), $strNext );
            } else {
                $anchorNext = $strNext;
            }

            return sprintf( '%s%s', sprintf($formatTag, $anchorPrev), sprintf($formatTag, $anchorNext) );
        }

        /**
         * ページ数を表示するページャー
         *
         * @return integer
         */
        public function pagerList() {
            $aryParameter = $this->getAryParameter();

            $formatTag = $this->_formatTag();

            $strPager = '';
            for ( $p = $aryParameter['start']; $p <= $aryParameter['end']; $p++ ) {
                if ( $p === $aryParameter['current_page'] ) {
                    $strPager .= sprintf( $formatTag, $p);
                } else {
                    $strPager .= sprintf( $formatTag, sprintf( '<a href="%s">%s</a>', $this->_getCurrentUrl($p), $p ) );
                }
            }

            return $strPager;
        }

        /**
         * タグを整形する
         *
         * @return string
         */
        private function _formatTag() {
            $formatTag = null;
            if ( null !== $this->getUseTag() ) {
                if ( null !== $this->getClassName() ) {
                    $formatTag = sprintf( '<%s class="%s">', $this->getUseTag(), $this->getClassName() ) . '%s' . sprintf('</%s>', $this->getUseTag() );
                } else {
                    $formatTag = sprintf( '<%s>', $this->getUseTag() ) . '%s' . sprintf('</%s>', $this->getUseTag() );
                }
            } else {
                $formatTag = '%s';
            }

            return $formatTag;
        }
    }