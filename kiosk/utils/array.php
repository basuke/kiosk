<?php

require_once 'kiosk/utils/type.php';

/*
	純粋な配列かどうか調べる
*/
function is_pure_array($array) {
	if (!is_array($array)) return false;
	
	foreach ($array as $index=>$value) {
		if (!is_integer($index)) return false;
	}
	
	return true;
}

/*
	array_first($array, $empty_value = null)
	配列の最初の要素を返す
	配列が空なら$empty_valueを返す
*/
function array_first($array, $empty_value = null) {
	if (count($array) == 0) return $empty_value;
	return array_shift($array);
}

/*
	array_to_object($class)
	配列を指定のクラスのインスタンスに変換する。
	値は全て、新しいインスタンスのプロパティになる
*/
function array_to_object($array, $class = null) {
	assert('is_array($array)');
	
	if (!is_string($class) or !class_exists($class)) return (object) $array;
	
	$obj = new $class();
	foreach ($array as $key=>$value) {
		$obj->$key = $value;
	}
	
	return $obj;
}

/*
	渡された配列を、数字が添字の純粋な配列と、連想配列のハッシュに分離する。
*/
function array_separate_array_and_hash($mix) {
	$array = array();
	$hash = array();
	
	foreach ($mix as $key => $value) {
		if (is_integer($key)) {
			$array[] =& $mix[$key];
		} else {
			$hash[$key] =& $mix[$key];
		}
	}
	
	return array($array, $hash);
}

/*
	渡された配列を調べ、 $array[$key]が定義されているか、
	$arrayの中に$keyが存在するか調べ、その値を返す
*/
function array_test_option($array, $key) {
	if (isset($array[$key])) return $array[$key];
	
	foreach ($array as $index => $value) {
		if (is_integer($index) and $value == $key) {
			return true;
		}
	}
	
	return false;
}

function array_collect_value($array, $key) {
	assert('is_string($key)');
	
	$result = array();
	foreach ($array as $value) {
		$result[] = get_keyed_value($value, $key);
	}
	return $result;
}

function qw($str, $limit=null) {
	if (is_array($str)) {
		$result = $str;
	} else {
		$result = preg_split('/\\s+/', trim($str), $limit);
	}
	
	if ($limit > 0 and count($result) < $limit) {
		$result = array_pad($result, $limit, null);
	}
	
	return $result;
}

function cs($str, $limit=null) {
	if (is_array($str)) return $str;
	return preg_split('/\\s*,\\s*/', trim($str), $limit);
}

function ds($str, $limit=null) {
	if (is_array($str)) return $str;
	return explode('.', trim($str), $limit);
}

