<?php

define('KIOSK_HOME', dirname(__FILE__));
define('KIOSK_LIB_DIR', KIOSK_HOME. '/kiosk');

define('KIOSK_ERROR_CONFIG', 'KIOSK:CONFIG:');
define('KIOSK_ERROR_SYNTAX', 'KIOSK:SYNTAX:');
define('KIOSK_ERROR_RUNTIME', 'KIOSK:RUNTIME:');

define('KIOSK_PHP_5', version_compare(PHP_VERSION, '5.0.0', '>='));
define('KIOSK_PHP_5_3', version_compare(PHP_VERSION, '5.3.0', '>='));
define('KIOSK_PHP_4', !KIOSK_PHP_5);

define('KIOSK_HAS_REAL_CLASS', KIOSK_PHP_5_3);
define('KIOSK_HAS_EXCEPTION', KIOSK_PHP_5);

require_once KIOSK_LIB_DIR. '/data/Object.php';
require_once KIOSK_LIB_DIR. '/Callable.php';
require_once KIOSK_LIB_DIR. '/Backend.php';
require_once KIOSK_LIB_DIR. '/Namer.php';

class Kiosk extends KioskObject {
	/*
	*/
	function configure($name, $value=null) {
		return $GLOBALS['_Kiosk_Backend']->configure($name, $value);
	}
	
	/*
	*/
	function &source($name, $value = null) {
		$data =& Kiosk_data();
		return $data->source($name, $value);
	}
	
	/*
	*/
	function namer() {
		return new Kiosk_Namer();
	}
	
	/*
		現在時刻をmicro秒単位で返す。
	*/
	function now() {
		list($usec, $sec) = explode(" ",microtime());
		return ((float) $usec + (float)$sec);
	}
	
	/*
		実行可能オブジェクトを生成して返す
	*/
	function func($func) {
		$args = func_get_args();
		array_shift($args);
		
		if (is_object($func) or (is_string($func) and class_exists($func))) {
			if (count($args) >= 1 and is_string($args[0])) {
				$func = array($func, array_shift($args));
			}
		}
		
		return new Kiosk_Callable($func, $args);
	}
}

