<?php
    class Dao
    {
        const DB_CHARSET    = 'UTF-8';
        const INPUT_CHARSET = 'UTF-8';

        private $dbType = '';
        private $dbHost = '';
        private $dbUser = '';
        private $dbPass = '';
        private $dbName = '';

        private $dbCharset = self::DB_CHARSET;
        private $inputCharset = self::INPUT_CHARSET;

        /**
         *
         * @var PDO
         */
        static private $pdo;

        /**
         *
         * @var PDOStatement
         */
        private $stmt;

        private $sql        = null;

        private $schema     = null;
        private $table      = null;
        private $sequence   = null;
        private $primaryKey = null;

        private $currentCount = 0;
        private $columnCount  = 0; //項目数
        private $rowCount     = 0; //桁数

        private $pathErrorLogFormat = '/var/www/log/db-error-log-%04d-%02d';
        private $pathErrorLog;

        private $insertId     = null;
        private $isSaveLog    = true; //ログを保存するか

        /**
         * 初期化
         */
        public function  __construct( $setting = array() ) {
            $this->pathErrorLog = sprintf( $this->pathErrorLogFormat, date('Y'), date('m') );
            
            if ( 0 !== count($setting) ) {
                $this->_setDefault($setting);
            } else {
                global $_config;
                $this->_setDefault($_config);
            }

            $this->_connect();
        }

        /**
         * 初期設定
         *
         * @param array $setting
         */
        private function _setDefault( $setting )
        {
            if ( isset($setting['db_type']) ) {
                $this->setType($setting['db_type']);
            }

            if ( isset($setting['db_host']) ) {
                $this->setHost($setting['db_host']);
            }

            if ( isset($setting['db_user']) ) {
                $this->setUser($setting['db_user']);
            }

            if ( isset($setting['db_pass']) ) {
                $this->setPass($setting['db_pass']);
            }

            if ( isset($setting['db_name']) ) {
                $this->setName($setting['db_name']);
            }

            if ( isset($setting['charset']) ) {
                $this->setDbCharset($setting['charset']);
            }

            if ( isset($setting['input']) ) {
                $this->setInputCharset($setting['input']);
            }
        }

        /**
         * PDO接続
         *
         * @return object
         */
        private function _connect()
        {
            if ( null === self::$pdo || $this->dbType !== self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ) {
                try {
                    self::$pdo = new PDO( sprintf("%s:host=%s; dbname=%s", $this->dbType, $this->dbHost, $this->dbName), $this->dbUser, $this->dbPass );
                    if ( $this->dbType === 'mysql' ) {
                        self::$pdo->setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true );

                        switch ( strtolower($this->dbCharset) ) {
                            case 'utf-8':
                            case 'utf8':
                            case 'utf':
                                self::$pdo->query("SET NAMES utf8;");
                                break;
                            case 'euc-jp':
                            case 'eucjp':
                            case 'ujis':
                                self::$pdo->query("SET NAMES ujis;");
                                break;
                        }
                        
                    }
                    self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                } catch (PDOException $e) {
                    //trigger_error($e->getMessage(), E_USER_ERROR);

                    $this->_errorLog('error', $e->getMessage(), null, __CLASS__, __METHOD__, __LINE__);
                    exit('データベースに接続出来ませんでした。');
                }
            }

            return self::$pdo;
        }

        public function setType( $type )
        {
            $this->dbType = $type;
        }

        public function setHost( $host )
        {
            $this->dbHost = $host;
        }

        public function setUser( $user )
        {
            $this->dbUser = $user;
        }

        public function setPass( $pass )
        {
            $this->dbPass = $pass;
        }

        public function setName( $name )
        {
            $this->dbName = $name;
        }

        public function setDbCharset( $charset )
        {
            $this->dbCharset = $charset;
        }

        public function setInputCharset( $charset )
        {
            $this->inputCharset = $charset;
        }

        /**
         * PDOインスタンスを取得
         *
         * @return PDO
         */
        public function getPdo()
        {
            return self::$pdo;
        }

        /**
         * SQLゲッター
         *
         * @return string
         */
        public function getSql()
        {
            return $this->sql;
        }
        
        /**
         *
         * @return PDOStatement
         */
        public function getStmt()
        {
            return $this->stmt;
        }

        /**
         * スキーマゲッター
         *
         * @return string
         */
        public function getSchema()
        {
            return $this->schema;
        }

        /**
         * スキーマセッター
         *
         * @param string $schema
         */
        public function setSchema($schema)
        {
            $this->schema = $schema;
        }

        /**
         * テーブルゲッター
         *
         * @return string
         */
        public function getTable()
        {
            return $this->table;
        }

        /**
         * テーブルセッター
         *
         * @param string $table
         */
        public function setTable( $table )
        {
            $this->table = $table;
        }

        /**
         * テーブル名取得
         *
         * @return string
         */
        public function getTableName()
        {
            if ( null === $this->getSchema() ) {
                return $this->getTable();
            } else {
                return $this->getSchema() . '.' . $this->getTable();
            }
        }

        /**
         * テーブルがセットされていればtrue
         *
         * @return Boolean
         */
        public function isSetTable() {
            if ( null === $this->getTable() ) {
                return false;
            }

            return true;
        }

        /**
         * シーケンスゲッター
         *
         * @return string
         */
        public function getSequence()
        {
            return $this->sequence;
        }

        /**
         * シーケンスセッター
         *
         * @param string $sequence
         */
        public function setSequence( $sequence )
        {
            $this->sequence = $sequence;
        }

        /**
         * 主キーゲッター
         *
         * @return string
         */
        public function getPrimaryKey()
        {
            return $this->primaryKey;
        }

        /**
         * 主キーセッター
         *
         * @param string $primaryKey
         */
        public function setPrimaryKey( $primaryKey )
        {
            $this->primaryKey = $primaryKey;
        }

        /**
         * 項目数取得
         *
         * @return integer
         */
        public function getColumnCount()
        {
            return $this->columnCount;
        }

        /**
         * 取得中の件数
         *
         * @return integer
         */
        public function getCurrentCount()
        {
            return $this->currentCount;
        }

        /**
         * 取得された件数
         *
         * @return integer
         */
        public function getRowCount()
        {
            return $this->rowCount;
        }

        /**
         * ログに保存チェック用フラグの取得
         *
         * @return boolean
         */
        public function getIsSaveLog()
        {
            return $this->isSaveLog;
        }

        /**
         * ログに保存チェック用フラグを設定
         * Boolean型じゃない場合はfalseを返す
         *
         * @param boolean $bool
         * @return boolean
         */
        public function setIsSaveLog( $bool )
        {
            if ( false === is_bool($bool) ) {
                return false;
            }

            $this->isSaveLog = $bool;
        }

        /**
         * 最後に挿入したIDを取得
         *
         * @return integer
         */
        public function getInsertId() {
            return $this->insertId;
        }

        /**
         * 最後に挿入したIDを設定
         *
         * @param integer $insert_id
         */
        public function setInsertId( $insert_id ) {
            $this->insertId = $insert_id;
        }

        /**
         * 次の値があればTRUE
         *
         * @return boolean
         */
        public function hasNext()
        {
            return $this->getCurrentCount() < $this->getRowCount();
        }

        /**
         * 次の値を取得
         *
         * @return object
         */
        public function next( $column = null )
        {
            $this->currentCount++;

            if ( null === $column ) {
                return $this->stmt->fetch( PDO::FETCH_OBJ );
            } else {
                $row = $this->stmt->fetch( PDO::FETCH_OBJ );

                $result = new stdClass();

                if ( true === is_string($column) && true === isset($row->$column) ) {
                    return $row->$column;
                } else if ( true === is_array($column) ) {
                    foreach ( $column as $field ) {
                        if ( isset( $row->$field ) ) {
                            $result->$field = $row->$field;
                        }
                    }
                }

                return $result;
            }
        }

        /**
         * VOから検索
         *
         * @param object $vo
         * @param string $order
         * @param integer $offset
         * @param integer $limit
         * @return boolean
         */
        public function find( $vo, $order = null, $offset = 0, $limit = null ) {
            if ( false === is_object($vo) && false === is_array($vo) ) {
                return false;
            }

            $aryValues = array();
            $aryWhere  = array();
            foreach ( $vo as $field => $value ) {
                if ( null === $value ) {
                    continue;
                }

                $aryWhere[] = "{$field} = :{$field}";
                $aryValues[$field] = $value;
            }

            if ( 0 === count($aryWhere) ) {
                return false;
            }

            $this->sql = sprintf("SELECT * FROM %s WHERE %s", $this->getTableName(), implode( ' AND ', $aryWhere ));
            if ( null !== $order ) {
                $this->sql .= sprintf( " ORDER BY %s", $this->_convertColumn($order) );
            }
            
            if ( 'pgsql' === $this->dbType ) {
                if ( true === is_numeric($offset) ) {
                    $this->sql .= " OFFSET {$offset}";
                }

                if ( true === is_numeric($limit) ) {
                    $this->sql .= " LIMIT {$limit}";
                }
            } else {
                if ( true === is_numeric($offset) && true === is_numeric($limit) ) {
                    $this->sql .= " LIMIT {$offset}, {$limit}";
                } else if ( true === is_numeric($limit) ) {
                    $this->sql .= " LIMIT {$limit}";
                }
            }

            $this->stmt = self::$pdo->prepare( $this->sql );
            try {
                $result = false;
                if (false !== $this->stmt) {
                    $result = $this->stmt->execute( $aryValues );

                    $this->columnCount  = $this->stmt->columnCount();
                    $this->rowCount     = $this->stmt->rowCount();
                } else {
                    $this->error( $this->stmt->errorInfo() );
                }

                $this->currentCount = 0;
            } catch ( PDOException $e ) {
                $this->_errorLog('query', $e->getMessage(), $this->getSql(), __CLASS__, __FUNCTION__, __LINE__);

                trigger_error('QUERY ERROR:' . $e->getMessage() . "\n" . $this->getSql(), E_USER_ERROR);
            }

            return $result;
        }

        /**
         * 全てを取得
         *
         * @return boolean
         */
        public function findAll()
        {
            if ( false === $this->isSetTable() ) {
                return false;
            }
            $result = $this->query( sprintf("SELECT * FROM %s;", $this->getTableName()) );

            return $result;
        }

        /**
         * カスタム検索
         *
         * @param string $colmun
         * @param string $where
         * @param string $group
         * @param string $order
         * @param integer $offset
         * @param integer $limit
         * @return boolean
         */
        public function findCustom( $column = '*', $where = null, $group = null, $order = null, $offset = 0, $limit = null )
        {
            if ( false === $this->isSetTable() ) {
                return false;
            }

            $this->sql = sprintf( "SELECT %s FROM %s", $this->_convertColumn($column), $this->getTableName() );

            if ( null !== $where ) {
                $this->sql .= sprintf( " WHERE %s", $this->_convertWhere($where) );
            }

            if ( null !== $group ) {
                $this->sql .= sprintf( " GROUP BY %s", $this->_convertColumn($group) );
            }

            if ( null !== $order ) {
                $this->sql .= sprintf( " ORDER BY %s", $this->_convertColumn($order) );
            }

            if ( 'pgsql' === $this->dbType ) {
                if ( true === is_numeric($offset) ) {
                    $this->sql .= " OFFSET {$offset}";
                }

                if ( true === is_numeric($limit) ) {
                    $this->sql .= " LIMIT {$limit}";
                }
            } else {
                if ( true === is_numeric($offset) && true === is_numeric($limit) ) {
                    $this->sql .= " LIMIT {$offset}, {$limit}";
                } else if ( true === is_numeric($limit) ) {
                    $this->sql .= " LIMIT {$limit}";
                }
            }
            

            return $this->query( $this->getSql() );
        }

        /**
         * SQLのカンマ区切りの形式にする
         *
         * @return string
         */
        protected function _convertColumn( $column )
        {
            if ( true === is_string($column) ) {
                return $column;
            } else if ( true === $this->_isNumberedArray($column) ) {
                return implode( ', ', $column );
            }

            return false;
        }

        /**
         * SQLのWHERE句の形式にする
         *
         * @return string
         */
        protected function _convertWhere( $where )
        {
            if ( true === is_string( $where ) ) {
                return $where;
            } else if ( true === $this->_isNumberedArray($where) ) {
                return implode( ' AND ', $where );
            } else if ( true === is_array( $where ) ) {
                $aryWhere = array();
                
                foreach ( $where as $key => $val ) {
                    if ( null === $val ) {
                        $aryWhere[] = "{$key} = NULL";
                    } else if ( true === is_int($val) || true === is_numeric($val) ) {
                        $aryWhere[] = "{$key} = {$val}";
                    } else if ( 'pgsql' === $this->dbType && true === is_bool($val) ) {
                        switch ( $val ) {
                            case true:
                                $aryWhere[] = "{$key} = true";
                                break;
                            case false:
                                $aryWhere[] = "{$key} = false";
                                break;
                        }
                    } else if ( true === is_string($val) ) {
                        $aryWhere[] = sprintf("{$key} = %s", $this->quote($val) );
                    }
                }

                return implode( ' AND ', $aryWhere );
            }
        }

        /**
         * 連番形式の配列であればtrue
         *
         * @return boolean
         */
        protected function _isNumberedArray( $var )
        {
            if ( false === is_array($var) ) {
                return false;
            }

            reset($var);
            list($k,) = each($var);
            return $k === 0;
        }

        /**
         * 件数取得
         *
         * @param string $where
         * @return integer
         */
        public function count( $where, $bind = array() )
        {
            $sqlWhere = $this->_convertWhere($where);

            $this->query( sprintf("SELECT COUNT(*) AS cnt FROM %s%s", $this->getTableName(), 0 !== strlen(trim($sqlWhere)) ? ' WHERE ' . $sqlWhere : ''), $bind );

            return $this->hasNext() ? (int)$this->next('cnt') : 0;
        }

        /**
         * VOの値をINSERT
         *
         * @param object|array $vo
         * @param boolean $isUseTransaction
         * @return boolean
         */
        public function insert( $vo, $isUseTransaction = true )
        {
            if (is_object($vo) ) {
                $aryInsert = $this->_convertObjectToArray($vo);
            } else if ( is_array($vo) ) {
                $aryInsert = $vo;
            } else {
                trigger_error("Insert data error", E_USER_ERROR);
            }
            
            $aryInsert = $this->_validateValues($aryInsert);

            if ( self::DB_CHARSET !== self::INPUT_CHARSET ) {
                $aryInsert = $this->_multiConvertEncoding($aryInsert, self::DB_CHARSET, self::INPUT_CHARSET);
            }

            $aryFields = array();
            $aryDefSet = array();
            $aryValues = array();

            foreach ( $aryInsert as $field => $val ) {

                if ( null === $val ) {
                    continue;
                }

                $aryFields[] = $field;
                $aryDefSet[ $field ] = sprintf( ':%s', $field );
                $aryValues[ $field ] = $val;
            }

            if ( 'pgsql' === $this->dbType && null !== $this->getSequence() ) {
                $this->sql  = sprintf("INSERT INTO %s (%s, %s) VALUES (nextval('%s'), %s);"
                                    , $this->getTableName()
                                    , $this->getPrimaryKey()
                                    , implode(', ', $aryFields)
                                    , $this->getSequence()
                                    , implode(', ', $aryDefSet) );
            } else {
                $this->sql  = sprintf("INSERT INTO %s (%s) VALUES (%s);"
                                    , $this->getTableName()
                                    , implode(', ', $aryFields)
                                    , implode(', ', $aryDefSet) );
            }
            
            $this->stmt = self::$pdo->prepare( $this->sql );

            try {
                $result = false;
                if ( false !== $this->stmt ) {

                    if ( true === $isUseTransaction ) {
                        self::$pdo->beginTransaction();
                    }
                    
                    $result = $this->stmt->execute( $aryValues );

                    if ( false === $result ) {
                        if ( true === $isUseTransaction ) {
                            self::$pdo->rollBack();
                        }
                        return false;
                    }

                    if ( 'mysql' === $this->dbType ) {
                        $this->setInsertId( (int)self::$pdo->lastInsertId() );
                    } else if ( 'pgsql' === $this->dbType && null !== $this->getSequence() ) {
                        $this->setInsertId( (int)self::$pdo->lastInsertId( $this->getSequence() ) );
                    }

                    if ( true === $isUseTransaction ) {
                        self::$pdo->commit();
                    }
                }
            } catch ( PDOException $e ) {
                $this->_errorLog('insert', $e->getMessage(), $this->getSql(), __CLASS__, __FUNCTION__, __LINE__, $vo);
                trigger_error('INSERT ERROR:' . $e->getMessage(), E_USER_ERROR);
            }

            return $result;
        }

        /**
         * 主キーの値をVOに更新
         *
         * @param object $vo
         * @param string $primaryVal
         * @param boolean $isUseTransaction
         * @return boolean
         */
        public function update( $vo, $primaryVal, $isUseTransaction = true )
        {
            if (is_object($vo) ) {
                $aryUpdate = $this->_convertObjectToArray($vo);
                $aryUpdate = $this->_unsetPrimaryKey($aryUpdate);
            } else if (is_array($vo)) {
                $aryUpdate = $vo;
            } else {
                return false;
            }

            $aryUpdate = $this->_validateValues($aryUpdate);

            if ( self::DB_CHARSET !== self::INPUT_CHARSET ) {
                $aryUpdate = $this->_multiConvertEncoding($aryInsert, self::DB_CHARSET, self::INPUT_CHARSET);
            }

            $aryDefSet = array();
            $aryValues = array();

            foreach ( $aryUpdate as $field => $val ) {
                $aryDefSet[] = sprintf( '%s = :%s', $field, $field );
                $aryValues[ $field ] = $val;
            }
            $aryValues[ $this->getPrimaryKey() ] = $primaryVal;

            $this->sql  = sprintf("UPDATE %s SET %s WHERE %s = :%s;"
                                , $this->getTableName()
                                , implode(', ', $aryDefSet)
                                , $this->getPrimaryKey()
                                , $this->getPrimaryKey() );

            $this->stmt = self::$pdo->prepare( $this->sql );

            try {
                $result = false;
                if ( false !== $this->stmt ) {
                    if ( true === $isUseTransaction ) {
                        self::$pdo->beginTransaction();
                    }

                    $result = $this->stmt->execute( $aryValues );

                    if ( false === $result ) {
                        if ( true === $isUseTransaction ) {
                            self::$pdo->rollBack();
                        }
                        return false;
                    }

                    if ( true === $isUseTransaction ) {
                        self::$pdo->commit();
                    }
                }
            } catch ( PDOException $e ) {
                $this->_errorLog('update', $e->getMessage(), $this->getSql(), __CLASS__, __FUNCTION__, __LINE__, $vo);

                trigger_error('UPDATE ERROR:' . $e->getMessage(), E_USER_ERROR);
            }

            return $result;
        }

        /**
         * 配列から主キーの値を更新する
         *
         * @param array $array
         * @param integer $primaryVal
         * @return boolean
         */
        public function updateFromArray( $array, $primaryVal )
        {
            if ( false === is_array( $array ) ) {
                return false;
            }

            $aryUpd = array();
            foreach ( $array as $key => $val ) {
                $aryUpd[] = "{$key} = :{$key}";
            }

            $this->sql = "UPDATE " . $this->getTableName() . " SET " . implode(', ', $aryUpd)
                       . " WHERE " . $this->getPrimaryKey() . " = :" . $this->getPrimaryKey();

            $array[ $this->getPrimaryKey() ] = $primaryVal;

            foreach ( $array as $column => $value ) {
                if ( 'pgsql' === $this->dbType && true === is_bool( $value ) ) {
                    switch( $value ) {
                        case true:
                            $array[ $column ] = 'true';
                            break;
                        case false:
                            $array[ $column ] = 'false';
                            break;
                    }
                }
            }

            return $this->query( $this->sql, $array );
        }

        /**
         * Transaction begin
         */
        public function begin()
        {
            self::$pdo->beginTransaction();
        }

        /**
         * Transaction rollback
         */
        public function rollback()
        {
            self::$pdo->rollBack();
        }

        /**
         * Transaction commit
         */
        public function commit()
        {
            self::$pdo->commit();
        }

        /**
         * 与えられたSQL文を実行する
         *
         * @param string $sql
         * @param array
         * @return boolean
         */
        public function query( $sql, $bind = array() )
        {
            $this->sql = $sql;
            $this->stmt = self::$pdo->prepare($this->sql);

            try {
                $result = false;
                if (false !== $this->stmt) {
                    foreach ( $bind as $field => $val ) {
                        $this->stmt->bindValue( ':' . $field, $val );
                    }

                    $result = $this->stmt->execute();

                    $this->columnCount  = $this->stmt->columnCount();
                    $this->rowCount     = $this->stmt->rowCount();
                } else {
                    $this->error( $this->stmt->errorInfo() );
                }

                $this->currentCount = 0;
            } catch ( PDOException $e ) {
                $this->_errorLog('query', $e->getMessage(), $this->getSql(), __CLASS__, __FUNCTION__, __LINE__);

                trigger_error('QUERY ERROR:' . $e->getMessage() . "\n" . $this->getSql(), E_USER_ERROR);
            }

            return $result;
        }

        /**
         * 配列から主キーを省く
         *
         * @param array $var
         * @return array
         */
        protected function _unsetPrimaryKey( $var )
        {
            $result = array();
            foreach ( $var as $key => $val ) {
                if ( $key !== $this->getPrimaryKey() ) {
                    $result[$key] = $val;
                }
            }
            unset( $var );
            
            return $result;
        }

        /**
         * オブジェクトを配列へ変換する
         *
         * @param object $obj
         * @return array
         */
        protected function _convertObjectToArray( $obj )
        {
            if ( true === is_object($obj) ) {
                $obj = get_object_vars($obj);
            }

            if ( true === is_array( $obj ) ) {
                foreach ( $obj as $key => $val ) {
                    $obj[$key] = $this->_convertObjectToArray($val);
                }
            }

            return $obj;
        }

        /**
         * 配列の文字コードを変換する
         *
         * @param array $var
         * @param string $to_encoding
         * @param string $from_encoding
         * @return array
         */
        protected function _multiConvertEncoding( $var, $to_encoding, $from_encoding = 'auto' )
        {
            if ( true === is_array( $var ) ) {
                foreach ( $var as $key => $val ) {
                    $var[$key] = $this->_multiConvertEncoding($val, $to_encoding, $from_encoding);
                }
            } else if ( true === is_string( $var ) ) {
                $var = mb_convert_encoding($var, $to_encoding, $from_encoding);
            }

            return $var;
        }

        /**
         * 直接挿入出来ない値をバリデート
         *
         * @param array $array
         * @return string
         */
        protected function _validateValues( $array ) {
            if ( false === is_array($array) ) {
                return false;
            }

            foreach ( $array as $field => $value ) {
                if ( true === is_bool($value) ) {
                    switch ( $value ) {
                        case true:
                            $array[$field] = 'true';
                            break;
                        case false:
                            $array[$field] = 'false';
                            break;
                    }
                }
            }

            return $array;
        }

        /**
         * 文字列をクオートする
         *
         * @param string $var
         * @return string quoted string
         */
        public function quote( $var )
        {
            if ( false === is_string($var) ) {
                return false;
            }

            return self::$pdo->quote( $var );
        }

        /**
         * LIKE用に文字列をクオートする
         * $positionで％の位置を設定する（b => both(両方), l => left(左), r => right(右)）
         *
         * @param string $var
         * @param b|l|r $position
         * @return string|bool
         */
        public function likeQuote( $var, $position = 'b' )
        {
            if ( false === is_string($var) ) {
                return false;
            }

            $var = str_replace('\\', '\\\\', $var);
            $var = str_replace('%', '\\%', $var);
            $var = str_replace('_', '\\_', $var);

            switch ( $position ) {
                case 'b':
                default:
                    $var = $this->quote('%' . $var . '%');
                    break;
                case 'l':
                    $var = $this->quote('%' . $var);
                    break;
                case 'r':
                    $var = $this->quote($var . '%');
                    break;
            }

            return $var;
        }

        /**
         * エラーをログに書き込む
         *
         * @param integer $type
         * @param string $msg
         * @param string $sql
         * @param string $class
         * @param string $function
         * @param integer $line
         * @param object $vo
         * @return boolean
         */
        private function _errorLog($type, $msg, $sql, $class, $function, $line, $vo = null )
        {
            if ( false === $this->getIsSaveLog() ) {
                return false;
            }

            $handle = fopen( $this->pathErrorLog, 'a' );
            if ( false === is_resource($handle) ) {
                return false;
            }

            $format = '[%s]%s %s %s %s %d : %s' . "\n";
            $result = fwrite( $handle, sprintf($format, strtoupper($type), date('Y/m/d H:i:s', time()), $msg, $class, $function, $line, $sql) );

            if ( $vo !== null ) {
                $strVo = null;

                foreach ( $vo as $key => $val ) {
                    $strVo .= sprintf( '[%s : %s]' . "\n", $key, $val );
                }

                $strVo .= "\n";
                fwrite( $handle, $strVo );
            }

            fclose($handle);

            return $result;
        }
    }