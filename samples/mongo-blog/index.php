<?php

/**
 *	Kiosk + MongoDBで作る簡易ブログ
 */

ini_set('display_errors', true);
error_reporting(E_ALL);



// 設定
require_once 'config.php';



// actionの実行

$action = (@$_GET['action'] ?: 'index');
$path = BASE_DIR. "/action/$action.php";

if (file_exists($path)) {
	include $path;
} else {
	die("File not found");
}



// 便利関数

function redirect($url) {
	header('Location: '. $url);
	exit(0);
}

