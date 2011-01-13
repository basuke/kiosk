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
	
	// Context =================================
	
	function context() {
		require_once KIOSK_LIB_DIR. '/app/Context.php';
		
		return new Kiosk_App_Context(); 
	}
	
	// Controller ==============================
	
	function loadController($controller) {
		$path = $this->controllerPath($controller);
		if (file_exists($path) == false) return false;
		
		require_once $path;
		
		return true;
	}
	
	function controllerPath($controller) {
		return $this->controllersDir(). $controller. '.php';
	}
	
	function runController($context) {
		$controller = $context->controller;
		$action = $context->action;
		
		$func = $controller. '__'. $action;
		if (function_exists($func)) {
			return $func($context);
		}
		
		if (class_exists($controller)) {
			$c =& new $controller();
			foreach ((array) $context as $key=>$value) {
				$c->$key = $value;
			}
			
			if (is_callable(array($c, $action))) {
				$c->$action();
				return (array) $c;
			}
		}
		
		return null;
	}
	
	// View ====================================
	
	function viewPath($context) {
		$controller = $context->controller;
		$action = $context->action;
		$type = $context->type;
		
		if (!$type) {
			$type = 'html';
		}
		
		if (!$action) {
			$action = 'index';
		}
		
		$path = "{$action}.{$type}";
		
		if ($controller) {
			$path = $controller. '/'. $path;
		}
		
		return $path;
	}
	
	function render($path, $vars) {
		$smarty = new Smarty();
		
		$smarty->template_dir = APP_VIEWS_DIR;
		$smarty->compile_dir  = APP_TMP_DIR. '/templates/';
		$smarty->config_dir   = APP_CONFIG_DIR. '/';
		$smarty->cache_dir    = APP_CACHE_DIR. '/';
		$smarty->plugins_dir = array('plugins', APP_LIBS_DIR. '/smarty-plugins');
		
		$smarty->caching = false;
		
		$smarty->assign('server', $_SERVER);
		
		foreach ($vars as $key=>$value) {
			$smarty->assign($key, $value);
		}
		
		if (DEVELOPMENT) {
			$smarty->assign('debug', Debug::debugInfo());
		}
		
		$smarty->display($path);
	}
}

