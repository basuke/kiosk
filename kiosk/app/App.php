<?php

/*
	Part of Kiosk framework.
	
	Written by Yosuke "Basuke" Suzuki. @basuke
*/

require_once KIOSK_LIB_DIR. '/app/Router.php';

class Kiosk_App_App {
	var $_router;
	var $_app_dir;
	
	function Kiosk_App_App() {
		$this->__construct();
	}
	
	function __construct() {
		$this->_router =& new Kiosk_App_Router();
	}
	
	// Application Directory ===================
	
	function setAppDir($path) {
		$this->_app_dir = $path;
	}
	
	function appDir() {
		return $this->_app_dir;
	}
	
	function controllersDir() {
		return $this->_app_dir. '/controllers/';
	}
	
	// Router ==================================
	
	function map($pattern, $params=array(), $options=array()) {
		$this->_router->map($pattern, $params, $options);
	}
	
	function route($url) {
		return $this->_router->route($url);
	}
	
	function url($params) {
		return $this->_router->url($params);
	}
	
	// Controller ==============================
	
	function handleController($controller, $action, $args) {
		if (!$action) {
			$args['action'] = $action = 'index';
		}
		
		$path = $this->controllersDir(). $controller. '.php';
		if (file_exists($path) == false) {
			return array();
			return set_http_error(403, 'Forbidden');
		}
		
		require_once $path;
		
		$func = $controller. '__'. $action;
		if (function_exists($func)) {
			return $func($args);
		}
		
		if (class_exists($controller)) {
			return $this->handleControllerClass($controller, $action, $args);
		}
		
		return array();
	}
	
	function handleControllerClass($class, $action, $args) {
		$controller =& new $class($args);
		foreach ($args as $key=>$value) {
			$controller->$key = $value;
		}
		
		if (is_callable(array($controller, $action)) == false) {
			Debug::log(LOG_ERR, "action {$action} not found");
			return set_http_error(403, 'Forbidden');
		}
		
		$controller->$action();
		
		return (array) $controller;
	}
	
}

