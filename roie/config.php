<?php
/*
------------------------------------------------------------
    
    ROIE-board v0.0.0 lot.210130.0
    by sakots >> https://dev.oekakibbs.net/

    ROIE-boardの設定ファイルです。
    まだ仮です。

------------------------------------------------------------
*/

define('TITLE', '練習ひとこと掲示板'); //けいじばんのなまえ
define('THEMEDIR', 'classic'); //テーマのフォルダ名
define('LOGFILE', 'dat.log'); //ログファイル名
define('PHP_SELF', 'index.php'); //スクリプト名(変更非推奨)

define('PERMISSION_FOR_DEST', 0606); //画像やHTMLファイルのパーミッション
define('PERMISSION_FOR_LOG', 0600); //ログのパーミッション
define('PERMISSION_FOR_DIR', 0707); //ディレクトリのパーミッション
