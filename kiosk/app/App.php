<?php

/*
	Part of Kiosk framework.
	
	Written by Yosuke "Basuke" Suzuki. @basuke
*/

class Kiosk_App_App {
	var $_router;
	
	function Kiosk_App_App() {
		$this->__construct();
	}
	
	function __construct() {
		$this->_router =& new Kiosk_App_Router();
	}
	
	function map($pattern, $params=array(), $options=array()) {
		$this->_router->map($pattern, $params, $options);
	}
	
	function route($url) {
		return $this->_router->route($url);
	}
	
	function url($params) {
		return $this->_router->url($params);
	}
}

