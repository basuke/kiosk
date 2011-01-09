<?php

class Kiosk_Callable {
	var $_callable;
	var $_args;
	
	function Kiosk_Callable($callable, $args=array()) {
		assert('is_callable($callable)');
		
		$this->_callable =& $callable;
		$this->_args =& $args;
	}
	
	function &call() {
		$args = func_get_args();
		$args = array_merge($this->_args, $args);
		$result =& call_user_func_array($this->_callable, $args);
		return $result;
	}
	
	function bind($arg) {
		$args = func_get_args();
		array_splice($this->_args, count($this->_args), 0, $args);
	}
}

