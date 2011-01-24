<?php

/*
	Part of Kiosk framework.
	
	Written by Yosuke "Basuke" Suzuki. @basuke
*/

require_once KIOSK_LIB_DIR. '/app/Router.php';

class Kiosk_App_App {
	var $_router;
	var $_app_dir;
	var $_logger;
	
	function Kiosk_App_App() {
		$this->__construct();
	}
	
	function __construct() {
		$this->_router =& new Kiosk_App_Router();
		
		set_error_handler(array($this, 'errorHandler'));
	}
	
	// Logger ==================================
	
	function setLogger(&$logger) {
		unset($this->_logger);
		$this->_logger =& $logger;
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
	
	// Error Handling ==========================
	
	function setDevelopment($flag) {
		if ($flag) {
			assert_options(ASSERT_ACTIVE, true);
			assert_options(ASSERT_WARNING, true);
			assert_options(ASSERT_BAIL, true);
			assert_options(ASSERT_QUIET_EVAL, true);
			assert_options(ASSERT_CALLBACK, array($this, 'assertHandler'));
		} else {
			assert_options(ASSERT_ACTIVE, 0);
			assert_options(ASSERT_WARNING, 0);
			assert_options(ASSERT_BAIL, 0);
			assert_options(ASSERT_QUIET_EVAL, 0);
		}
	}
	
	function assertHandler($file, $line, $message) {
		echo("assert fail: $message");
	}
	
	function errorHandler($errno, $errstr, $errfile, $errline) {
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting
			return;
		}
		
		$msg = "[$errno] $errstr in file {$errfile} at line [{$errline}]";
		
		switch ($errno) {
			case E_ERROR:
			case E_USER_ERROR:
				echo "<b>ERROR</b> [$errno] $errstr<br />\n";
				echo "  Fatal error on line $errline in file $errfile";
				echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
				echo "Aborting...<br />\n";
				exit(1);
				break;
			
			case E_WARNING:
			case E_USER_WARNING:
				$this->_logger->log(LOG_WARNING, $msg);
				break;
			
			case E_NOTICE:
			case E_USER_NOTICE:
				$this->_logger->log(LOG_NOTICE, $msg);
				break;
			
			default:
				$this->_logger->log(LOG_NOTICE, $msg);
				break;
		}
		
		return true;	/* true = Don't execute PHP internal error handler */
	}
	
	// Router ==================================
	
	function map($pattern, $params=array(), $options=array()) {
		$this->_router->map($pattern, $params, $options);
	}
	
	function route($url, &$context) {
		$route = $this->_router->route($url);
		if (! $route) {
			$context->setHTTPStatus(404, 'File not found.');
			return;
		}
		
		// ルーティングの結果で情報をアップデート
		$context->setRouteResult($route);
		
		if (! $context->action) {
			$context->action = 'index';
		}
		
		// コントローラを実行
		if (! $context->controller) return;
		
		$result = $this->handleController($context);
		if (! $result) return;
		
		// コントローラの結果で情報をアップデート
		$context->setControllerResult($result);
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
	
	function handleController(&$context) {
		if (! $this->loadController($context->controller)) {
			return array();
		}
		
		return $this->runController($context);
	}
	
	function loadController($controller) {
		$path = $this->controllerPath($controller);
		if (file_exists($path) == false) return false;
		
		require_once $path;
		
		return true;
	}
	
	function controllerPath($controller) {
		return $this->controllersDir(). $controller. '.php';
	}
	
	function runController(&$context) {
		$controller = $context->controller;
		$action = $context->action;
		
		$func = $controller. '__'. $action;
		if (function_exists($func)) {
			return $func($context);
		}
		
		if (class_exists($controller)) {
			$c =& new $controller();
			
			$before = $context->variables();
			
			foreach ($before as $key=>$value) {
				if (is_null($value)) continue;
				if (isset($c->$key)) continue;
				
				$c->$key = $value;
			}
			
			$result = null;
			
			if (is_callable(array($c, $action))) {
				$result = $c->$action($context);
			}
			
			if (! is_array($result)) {
				$result = array();
			}
			
			foreach ((array) $c as $key => $value) {
				if (!isset($before[$key]) or $before[$key] !== $value) {
					$result[$key] = $value;
				}
			}
			
			return $result;
		}
		
		return null;
	}
	
	// View ====================================
	
	function renderResponse(&$context) {
		// 準備オッケー
		$this->_logger->log('will start rendering views');
		
		$view = $this->viewPath($context);
		$context->view = $view;
		
		if (file_exists(APP_VIEWS_DIR. '/'. $view) == false) {
			$this->_logger->log(LOG_ERR, "view file {$view} not found in views.");
			$context->setHTTPStatus(404, 'File not found');
			return;
		}
		
		$vars = $context->variables();
		
		if (file_exists(APP_VIEWS_DIR. "/layouts/{$context->layout}.html")) {
			$this->renderHtml("layouts/{$context->layout}.html", $vars);
		} else {
			$this->renderHtml($view, $vars);
			
			if (DEVELOPMENT) {
				$this->renderHtml('elements/debug_console.html', $vars);
			}
		}
		
		$this->_logger->log('finished rendering views');
	}
	
	function viewPath(&$context) {
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
	
	function renderHtml($path, $vars) {
		require_once KIOSK_LIB_DIR. '/app/Smarty.php';
		$smarty = new KioskSmarty();
		
		foreach ($vars as $key=>$value) {
			$smarty->assign($key, $value);
		}
		
		$smarty->display($path);
	}
	
	function missingTemplate($type, $name, $source, $timestamp, $smarty) {
		return false;
	}
}

class KioskApp extends Kiosk_App_App {
}

