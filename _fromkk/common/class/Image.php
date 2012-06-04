<?php
    
    /**
     * 画像サイズを変更して表示、もしくは保存
     *
     * @package atFramework
     * @author Kazuya Ueoka
     * @version 0.1
     */
    class Image
    {
        var $resource
          , $widht
          , $height
          , $newImage
          , $type
          , $bgcolor;

        /**
         * 画像を表示する
         */
        const IMAGE_RESIZE_MODE_DISP = 0;

        /**
         * 画像を保存する
         */
        const IMAGE_RESIZE_MODE_SAVE = 1;

        /**
         * $pathで渡された画像のサイズを変更する
         *
         * @param string $path
         * @param integer $width
         * @param integer $height
         * @param integer $mode
         * @param string $save_path
         * @return boolean
         */
        function resize($path, $width = 0, $height = 0, $mode = self::IMAGE_RESIZE_MODE_DISP, $save_path = '')
        {
            $this->resource = $this->getImage($path);

            if ( false === $this->resource ) {
                return false;
            }
            $aryBaseSize = $this->getImageSize($path);
            $aryNewSize  = $this->getAryNewSize($path, $width, $height);

            $this->newImage = $this->getNewImage($aryNewSize['width'], $aryNewSize['height']);

            imagecopyresampled($this->newImage, $this->resource, 0, 0, 0, 0, $aryNewSize['width'], $aryNewSize['height'], $aryBaseSize['width'], $aryBaseSize['height']);

            if (self::IMAGE_RESIZE_MODE_DISP === $mode) {
                $this->display($path);
            } else {
                $this->save($path, $save_path);
            }

        }
        
        public function square($path, $width, $mode = self::IMAGE_RESIZE_MODE_DISP, $save_path = '') {
            $this->resource = $this->getImage($path);
            
            if ( false === $this->resource ) {
                return false;
            }
            
            $aryBaseSize = $this->getImageSize($path);

            $this->newImage = $this->getNewImage($width, $width);
            
            $shortLength = 0;
            $baseTop     = 0;
            $baseLeft    = 0;
            if ( $aryBaseSize['width'] > $aryBaseSize['height'] ) {
                $shortLength = $aryBaseSize['height'];
                $baseTop     = 0;
                $baseLeft    = (int)(($aryBaseSize['width'] - $shortLength) / 2);
            } else {
                $shortLength = $aryBaseSize['width'];
                
                if ( 0 != $aryBaseSize['height'] - $shortLength ) {
                    $baseTop     = (int)(($aryBaseSize['height'] - $shortLength) / 2);
                } else {
                    $baseTop     = 0;
                }
                
                $baseLeft    = 0;
            }
            
            imagecopyresampled($this->newImage, $this->resource, 0, 0, $baseLeft, $baseTop, $width, $width, $shortLength, $shortLength);
            
            if (self::IMAGE_RESIZE_MODE_DISP === $mode) {
                $this->display($path);
            } else {
                $this->save($path, $save_path);
            }
        }

        /**
         * 新しい画像を生成する
         *
         * @param integer $width
         * @param integer $height
         * @return resource
         */
        function getNewImage($width, $height)
        {
            $rs = imagecreatetruecolor($width, $height);

            $this->bgcolor = imagecolorallocatealpha($this->resource, 0, 0, 0, 127);
            imagefill($rs, 0, 0, $this->bgcolor);
            imagecolortransparent($rs, $this->bgcolor);

            return $rs;
        }

        /**
         * 画像を表示する
         *
         * @param string $path
         */
        function display($path)
        {
            $this->type = strtolower($this->extension($path));

            switch($this->type) {
                case 'jpg':
                case 'jpeg':
                    header("Content-Type:image/jpeg");
                    imagejpeg($this->newImage);
                    break;
                case 'gif':
                    header("Content-Type:image/gif");
                    imagegif($this->newImage);
                    break;
                case 'png':
                    header("Content-Type:image/png");
                    imagepng($path);
                    break;
                default:
                    exit();
            }
        }

        /**
         * 画像を保存する
         *
         * @param string $path
         * @param strig $save_path
         * @return boolean
         */
        function save($path, $save_path)
        {
            $this->type = strtolower($this->extension($path));

            $saveFilePath = $save_path . '/' . basename($path);

            switch($this->type) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($this->newImage, $saveFilePath);
                    break;
                case 'gif':
                    imagegif($this->newImage, $saveFilePath);
                    break;
                case 'png':
                    imagepng($this->newImage, $saveFilePath);
                    break;
                default:
                    exit();
            }
            chmod( $saveFilePath, 0644 );

            return true;
        }

        /**
         * 画像のサイズを取得する
         *
         * @param string$path
         * @return array
         */
        function getImageSize($path)
        {
            list($width, $height) = getimagesize($path);

            return array('width' => $width, 'height' => $height);
        }

        /**
         * 新しい画像のサイズを返す
         *
         * @param string $path
         * @param integer $width
         * @param integer $height
         * @return array
         */
        function getAryNewSize($path, $width = 0, $height = 0)
        {
            $aryGetSize = $this->getImageSize($path);

            if (0 === $width) {
                $aspect = $height / $aryGetSize['height'];
                $width  = (int)($aryGetSize['width'] * $aspect);
            } else if(0 === $height) {
                $aspect = $width / $aryGetSize['width'];
                $height = (int)($aryGetSize['height'] * $aspect);
            }

            if ($aryGetSize['width'] < $width && $aryGetSize['height'] < $height) {
                $return = $aryGetSize;
            } else {
                $return = array('width' => $width, 'height' => $height);
            }

            return $return;
        }

        /**
         * 画像かどうか判断する
         *
         * @param string $path
         * @return boolean
         */
        function isImage($path)
        {
            $this->type = strtolower($this->extension($path));

            switch( $this->type ) {
                case 'jpg':
                case 'jpeg':
                case 'gif':
                case 'png':
                    return true;
                    break;
                default:
                    return false;
                    break;
            }
        }

        /**
         * 画像を取得する
         *
         * @param string $path
         * @return boolean
         */
        function getImage($path)
        {

            if (  false === $this->isImage($path) ) {
                return false;
            }

            switch ($this->type) {
                case 'jpg':
                case 'jpeg':
                    $rs = imagecreatefromjpeg($path);
                    break;
                case 'gif':
                    $rs = imagecreatefromgif($path);
                    break;
                case 'png':
                    $rs = imagecreatefrompng($path);
                    break;
                default:
                    $rs = false;
            }

            return $rs;
        }

        /**
         * 拡張子を取得する
         *
         * @param string $path
         * @return string
         */
        private function extension($path)
        {
            return  pathinfo( $path, PATHINFO_EXTENSION );
        }
    }
