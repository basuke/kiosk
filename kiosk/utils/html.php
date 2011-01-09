<?php

/* 
	hn(..) 文字列をHTMLエスケープして出力する。改行は<br>に。
*/
function hn($str) {
	$args = func_get_args();
	_echo($args, array('htmlspecialchars', 'nl2br'));
}

/* 
	h(..) 文字列をHTMLエスケープして出力する。改行はそのまま。
	フォームのTEXTAREAでは、これを使うべし。
*/
function h($str) {
	$args = func_get_args();
	_echo($args, array('htmlspecialchars'));
}

/*
*/
function tr($str, $max, $ellipsis='…') {
	// 空白文字の連続を' 'に変換
	$str = preg_replace('/\\s+/', ' ', $str);
	
	$len = mb_strlen($str);
	if ($len > $max) {
		$str = mb_substr($str, 0, $max). $ellipsis;
	}
	
	echo htmlspecialchars($str);
}

/*
*/
function d($value) {
	if (is_array($value) or is_object($value)) {
		echo '<pre>';
		var_export($value);
		echo '</pre>';
	} else {
		h(''. $value);
	}
}

/* 
	ue($str) 文字列をURLエンコード処理をして出力する
*/
function ue($str) {
	$args = func_get_args();
	_echo($args, array('urlencode'));
}

/* 
	js($str) 文字列をJavaScript用の文字列エスケープ処理をして出力する
*/
function js($str) {
	$args = func_get_args();
	echo addcslashes(join('', $args), "\\\'\"&\n\r<>");
}

/* 
	p(..) 文字列をそのまま出力する
*/
function p($str) {
	$args = func_get_args();
	_echo($args);
}

function _echo($strs, $funcs=array()) {
	foreach ($funcs as $func) {
		$strs = array_map($func, $strs);
	}
	$str = join('', $strs);
	echo $str;
}

