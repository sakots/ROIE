<?php

//ROIE-board version
define('ROIE_VER' , 'v0.0.0');
define('ROIE_VERLOT' , 'v0.0.0 lot.210130.0');

/*
------------------------------------------------------------

    ROIE-board main program :
    ROIE-board (c) 2021 sakots and OekakiBBS dev.Team
    >> https://dev.oekakibbs.net/

    OEKAKI applet : (予定)
    PaintBBS, ShiPainter, PCHViewer (C) 2004 shi-chan
    >> http://hp.vector.co.jp/authors/VA016309/

    USED FUNCTION : (予定)
    DynamicPalette (c) 2005 noraneko
    >> (wondercatstudio.com)
    PAINTBBS NEO (c) 2016-2020 funige
    >> https://github.com/funige/neo
    
    TEMPLATE ENGINE :
    Twig (c) 2009-2021 the Twig Team
    >> https://twig.symfony.com/

------------------------------------------------------------
*/

if (($phpver = phpversion()) < "7.2.5") {
	die("PHP version 7.2.5 or higher is required for this program to work. <br>\n（Current PHP version:{$phpver}）");
}

//config読み込み
require(__DIR__.'/config.php');

//Twig読み込み
require_once(__DIR__.'/vendor/autoload.php');
//テンプレート読み込み
$loader = new \Twig\Loader\FilesystemLoader(__DIR__.'/theme/'.THEMEDIR);
$twig = new \Twig\Environment($loader, 
    ['cache' => __DIR__.'/cache','debug' => true]
);
//テーマ設定ファイル読み込み
require(__DIR__.'/theme/'.THEMEDIR.'/theme_conf.php');

//----------

//本体開始
session_start(); //CSRF対策

//モード
$mode = filter_input(INPUT_POST, 'mode');
$mode = $mode ? $mode : filter_input(INPUT_GET, 'mode');

switch($mode){
    case 'post':
        return posted();
    case 'replyto':
        return reply();
    case 'reppost':
        return reppost();
    default:
        def_disp();
}

//投稿終了後（スレ）
function posted(){
    //変数取得(POST)
    $name = (string)filter_input(INPUT_POST, 'name');
    $text = (string)filter_input(INPUT_POST, 'text');
    $token = (string)filter_input(INPUT_POST, 'token'); //CSRFトークン

    $oya_flag = 1;

    //改めてログを数えてレス番号を決定。一番大きい数字+1
    $fp = fopen(LOGFILE, 'a+b');
    $rows = array();
    flock($fp, LOCK_SH);
    while ($row = fgetcsv($fp)) {
        $rows[] = $row;
    }
    rewind($fp);
    $noarray = array_column($rows, 0);
    if (!empty($noarray)) {
        $no = (max($noarray) + 1);
    } else {
        $no = 1;
    }
    //スレ立て（親）なので%oya_noも同じ
    $oya_no = $no;

    //ログに書き込み
    $fp = fopen(LOGFILE, 'a+b');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && sha1(session_id()) === $token) { //CSRFトークン
        $time = time(); 
        $logtime = $time.substr(microtime(),2,3); //時間をこまかく
        $logstump = ((int)$logtime * 1000000); //スレ順
        $age = 1; //age用(つかってない)
        $soudane = 0; //そうだね
        flock($fp, LOCK_EX);
        fputcsv($fp, [$no, $name, $text, $oya_flag, $oya_no, $logstump, $logtime, $age, $soudane]);
        rewind($fp);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    //戻る
    session_regenerate_id(true);
    header("Location: ".PHP_SELF );
}

//リプ画面モード
function reply(){
    global $twig;
    //変数取得(GET)
    $repno = (filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT) - 1);
    //リプ先検索
    $fp = fopen(LOGFILE, 'a+b');
    flock($fp, LOCK_SH);
    while ($row = fgetcsv($fp)) {
        $rows[] = $row;
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    //存在しないスレにリプはできないようにしたい
    if (!isset($rows[$repno])) {
        error('そんなスレないです。');
    }
    $rep_rows = $rows[$repno];

    //リプ並び替え
    foreach ((array) $rows as $key => $value) {
            $_repsort[$key] = $value[5];
    }
    array_multisort($_repsort, SORT_DESC, $rows);

    $session_id = sha1(session_id());
    //出力
    echo $twig->render(
        'reply.html', [
            'themedir' => './theme/'.THEMEDIR,
            'self' => PHP_SELF,
            'title' => TITLE,
            'ver' => ROIE_VER,
            'lot' => ROIE_VERLOT,
            'tver' =>TEMPLATE_VER,
            'rows' => $rows,
            'repto' => $rep_rows,
            'session_id' => $session_id,
        ]
    );
}

//リプ投稿終了後
function reppost(){
    //変数取得(POST)
    $name = (string)filter_input(INPUT_POST, 'name');
    $text = (string)filter_input(INPUT_POST, 'text');
    $oya_no = (string)filter_input(INPUT_POST, 'oya_no');
    $oya_time = (string)filter_input(INPUT_POST, 'oya_time');
    $age = (string)filter_input(INPUT_POST, 'age');
    $token = (string)filter_input(INPUT_POST, 'token'); //CSRFトークン

    $oya_flag = 0;

    //改めてログを数えてレス番号を決定。一番大きい数字+1
    $fp = fopen(LOGFILE, 'a+b');
    $rows = array();
    flock($fp, LOCK_SH);
    while ($row = fgetcsv($fp)) {
        $rows[] = $row;
    }
    rewind($fp);
    $noarray = array_column($rows, 0);
    $no = (max($noarray) + 1);

    //ログに書き込み
    $fp = fopen(LOGFILE, 'a+b');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && sha1(session_id()) === $token) { //CSRFトークン
        $time = time(); 
        $logtime = $time.substr(microtime(),2,3); //時間をこまかく
        $logstump = ($oya_time - (int)$no); //スレ順
        $age += 1; //age用(つかってない)
        $soudane = 0; //そうだね
        flock($fp, LOCK_EX);
        fputcsv($fp, [$no, $name, $text, $oya_flag, $oya_no, $logstump, $logtime, $age, $soudane]);
        rewind($fp);
        //age処理 - 一旦ログを全部メモリに読み込む -> ログを1行ずつ書き出しながら -> 
        //$oya_noが一致するスレを全部ageるために$logstumpにtime()を足す
        while ($row = fgetcsv($fp, 65536, ",")) {
            $l_row[] = $row;
        }
        // ↑メモリに読み込んだ
        //現在のファイルを削除
        unlink(LOGFILE);
        flock($fp, LOCK_UN);
        fclose($fp);
        //配列を読んでいく
        $fp = fopen(LOGFILE, 'a+b');
        chmod(LOGFILE, PERMISSION_FOR_LOG);
        flock($fp, LOCK_EX);
        foreach ($l_row as $line => $values) {
            if ($l_row[$line][4] == $oya_no) {
                $values[5] = (string)((int)$values[5] + time());
            } else {
                $values[5] = $logstump;
            }
            //1行ずつ書いてく
            fputcsv($fp, [
                $values[0],
                $values[1],
                $values[2],
                $values[3],
                $values[4],
                $values[5],
                $values[6],
                $values[7],
                $values[8]
            ]);
        }
    }
    flock($fp, LOCK_UN);
    fclose($fp);

    //戻る
    session_regenerate_id(true);
    header("Location: ".PHP_SELF );
}

//通常時
function def_disp(){
    global $twig;
    //書き込みログ表示
    $fp = fopen(LOGFILE, 'a+b');
    chmod(LOGFILE, PERMISSION_FOR_LOG);
    $rows = array();
    flock($fp, LOCK_SH);
    while ($row = fgetcsv($fp)) {
        $rows[] = $row;
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    //順番入れ替え
    if (!empty($rows)) {
        foreach ((array) $rows as $key => $value) {
            $_sort[$key] = $value[5];
        }
        array_multisort($_sort, SORT_DESC, $rows);
    }

    $session_id = sha1(session_id());
    //出力
    echo $twig->render(
        'main.html', [
            'themedir' => './theme/'.THEMEDIR,
            'self' => PHP_SELF,
            'title' => TITLE,
            'ver' => ROIE_VER,
            'lot' => ROIE_VERLOT,
            'tver' =>TEMPLATE_VER,
            'rows' => $rows,
            'session_id' => $session_id,
        ]
    );
}

//エラー画面
function error($mes){
    global $twig;
    echo $twig->render(
        'error.html', [
            'themedir' => './theme/'.THEMEDIR,
            'self' => PHP_SELF,
            'title' => TITLE,
            'ver' => ROIE_VER,
            'lot' => ROIE_VERLOT,
            'tver' =>TEMPLATE_VER,
            'messege' => $mes,
        ]
    );
}
