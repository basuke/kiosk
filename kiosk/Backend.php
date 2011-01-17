<?php

require_once KIOSK_LIB_DIR. '/data/Data.php';

define('KIOSK_RESET_ALL', 0xffffffff);
define('KIOSK_RESET_SINGLETON', 0x0001);

/*

array(
	'name' => テーブル名 : 省略時はクラス名
	
	'refersTo => array(
		参照先クラス名 => array(
			'column' => 参照カラム名 : 省略時は（参照先テーブル名＋ _id）
		)
	)
)

*/

class Kiosk_Backend {
	var $_data;
	var $_config = array(
		'nameConversion' => false, // inflectorを使った名前の変換を行うか？
		'simpletest' => '', 
	);
	var $_singleton = array();
	
	function Kiosk_Backend() {
		$this->_data =& new Kiosk_Data();
	}
	
	function configure($name, $value=null) {
		if (isset($this->_config[$name]) == false) {
			return trigger_error(KIOSK_ERROR_CONFIG. "unknown configuration '{$name}'");
		}
		
		$current = $this->_config[$name];
		
		if (is_null($value) == false) {
			$checker = $name. 'Check';
			
			if (is_callable(array($this, $checker))) {
				$error = $this->$checker($value);
				if ($error) {
					return trigger_error(KIOSK_ERROR_CONFIG. $error);
				}
			}
			
			$this->_config[$name] = $value;
		}
		
		return $current;
	}
	
	function nameConversionCheck($value) {
		if ($value) {
			if (class_exists('Inflector') == false) {
				return "nameConversion requires CakePHP's Inflector class";
			}
		}
	}
	
	function simpletestCheck($value) {
		require_once KIOSK_LIB_DIR. '/test/Test.php';
		
		$test =& $this->singleton('Kiosk_Test_Test');
		return $test->setupSimpletest($value);
	}
	
	function &data() {
		return $this->_data;
	}
	
	function reset($flags) {
		$this->_data->reset($flags);
		
		if ($flags & KIOSK_RESET_SINGLETON) {
			$this->_singleton = array();
		}
	}
	
	function &singleton($class) {
		if (!isset($this->_singleton[$class])) {
			if (!class_exists($class)) {
				return trigger_error(KIOSK_ERROR_CONFIG. "{$class} class not exists");
			}
			
			$this->_singleton[$class] = & new $class();
		}
		
		return $this->_singleton[$class];
	}
	
}

$GLOBALS['_Kiosk_Backend'] =& new Kiosk_Backend();

function &Kiosk_backend() {
	return $GLOBALS['_Kiosk_Backend'];
}

function &Kiosk_data() {
	return $GLOBALS['_Kiosk_Backend']->data();
}

function Kiosk_isClass($test, $class) {
	return strcasecmp($test, $class) == 0;
}

function Kiosk_bind($class, &$db, $desc = array()) {
	$data =& Kiosk_data();
	$data->bind($class, $db, $desc);
}

function Kiosk_finalize() {
	$data =& Kiosk_data();
	$data->finalize();
}

function Kiosk_reset($flags = KIOSK_RESET_ALL) {
	$GLOBALS['_Kiosk_Backend']->reset($flags);
}

function &Kiosk_singleton($class) {
	return $GLOBALS['_Kiosk_Backend']->singleton($class);
}

