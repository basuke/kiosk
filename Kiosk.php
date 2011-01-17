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

if (defined('E_DEPRECATED')) {
	error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

require_once KIOSK_LIB_DIR. '/data/Object.php';
require_once KIOSK_LIB_DIR. '/Backend.php';

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
		require_once KIOSK_LIB_DIR. '/Namer.php';
		return new Kiosk_Namer();
	}
	
	/*
	*/
	function &app($dir = null) {
		require_once KIOSK_LIB_DIR. '/app/App.php';
		
		$app =& $GLOBALS['_Kiosk_Backend']->singleton('Kiosk_App_App');
		if ($dir) $app->setAppDir($dir);
		
		return $app;
	}
	
	/**
	 *	kiosk/utils/内に収められるユーティリティクラスのインスタンスを返す
	 *	
	 *	@param $name クラス名（Kiosk_Utils_HTTPなら "HTTP"）
	 *	@return インスタンス 
	 *	@access public
	 */
	function util($name) {
		$class = 'Kiosk_Utils_'. $name;
		
		if (! class_exists($class)) {
			$path = KIOSK_LIB_DIR. '/utils/'. $name. '.php';
			if (! file_exists($path)) return null;
			
			require_once $path;
			
			if (! class_exists($class)) return null;
		}
		
		return $GLOBALS['_Kiosk_Backend']->singleton($class);
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
		require_once KIOSK_LIB_DIR. '/Callable.php';
		
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

