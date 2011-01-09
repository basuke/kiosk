<?php

/*
	絶対URLを返す。
	現在の環境から、足りない情報を保管する。
	$_SERVERに依存する
*/
function absolute_url($url) {
	if (strpos($url, '://') !== false) return $url;
	
	if (substr($url, 0, 1) != '/') {
		$path = $_SERVER['REQUEST_URI'];
		if (substr($path, -1, 1) == '/') {
			$path = substr($path, 0, strlen($path) -1);
		} else {
			$path = dirname($path);
		}
		
		$url = $path. '/'. $url;
	}
	
	$scheme = $_SERVER['HTTP_HTTPS'] ? 'https' : 'http';
	return $scheme. '://'. $_SERVER['HTTP_HOST']. $url;
}

/*
	現在実行中のURLを返す。
	$_SERVERに依存する
*/
function self_url() {
	$scheme = $_SERVER['HTTP_HTTPS'] ? 'https' : 'http';
	return $scheme. '://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
}

/*
	指定のURLが、パターンにマッチしているか調べる。
	パターンは、文字列、もしくは文字列の配列で、
	書式はシェルのワイルドカード方式
*/
function url_match($test_url, $patterns) {
	$test_url = strtolower($test_url);
	
	foreach ((array) $patterns as $pattern) {
		if (fnmatch($pattern, $test_url)) {
			return true;
		}
	}
	
	return false;
}

