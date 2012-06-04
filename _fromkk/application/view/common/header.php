<!DOCTYPE html>
<html lang="ja">
    <head>
        <title><?php echo $this->p->which($this->_('title'), $this->_('title', 'e') . ' | ');
                  $this->oc('title', 'e');
            ?></title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width" />
        <link rel="stylesheet" href="<?php $this->oc('top', 'e'); ?>css/reset_html5.css" />
        <link rel="stylesheet" href="<?php $this->oc('top', 'e'); ?>css/bootstrap.min.css" />
        <link rel="stylesheet" href="<?php $this->oc('top', 'e'); ?>css/common.css" />
        <script type="text/javascript" src="<?php $this->oc('top', 'e'); ?>js/jquery.js"></script>
        <script type="text/javascript" src="<?php $this->oc('top', 'e'); ?>js/bootstrap.min.js"></script>
        <!--[if lt IE 9]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
        <script src="http://css3-mediaqueries-js.googlecode.com/svn/trunk/css3-mediaqueries.js"></script>
        <![endif]-->
    </head>
    <body>
        <div class="container">
            <h1 id="logo"><?php $this->oc('title', 'e'); ?></h1>
            