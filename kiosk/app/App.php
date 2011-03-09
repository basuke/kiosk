<?php

/*
	Part of Kiosk framework.
	
	Written by Yosuke "Basuke" Suzuki. @basuke
*/

require_once KIOSK_LIB_DIR. '/app/Router.php';
require_once KIOSK_LIB_DIR. '/Logger.php';
require_once KIOSK_LIB_DIR. '/app/renderers/HTML.php';
require_once KIOSK_LIB_DIR. '/app/renderers/JSON.php';

class Kiosk_App_App {
	var $_router;
	var $_app_dir;
	var $_logger;
	var $_renderers = array();
	
	function Kiosk_App_App() {
		$this->__construct();
	}
	
	function __construct() {
		$this->_router =& new Kiosk_App_Router();
		
		set_error_handler(array($this, 'errorHandler'));
		
		$logger = new Kiosk_Logger();
		$this->setLogger($logger);
		
		$this->registerRenderer(new Kiosk_App_HTMLRenderer(), 'html');
		$this->registerRenderer(new Kiosk_App_JSONRenderer(), 'json');
	}
	
	// Logger ==================================
	
	function setLogger(&$logger) {
		unset($this->_logger);
		$this->_logger =& $logger;
	}
	
	function logger() {
		return $this->_logger;
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
		$result = $context->apply($route);
		$context->setRouteResult($result);
		
		if (! $context->action) {
			$context->action = 'index';
		}
		
		// コントローラを実行
		if (! $context->controller) return;
		
		$this->handleController($context);
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
		if ($this->loadController($context->controller)) {
			$result = $this->runController($context);
			
			if (! is_null($result)) {
				$result = $context->apply($result);
				$context->setControllerResult($result);
			}
		}
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
			
			// コントローラに環境変数を設定
			foreach ($before as $key=>$value) {
				if (is_null($value)) continue;
				if (isset($c->$key)) continue;
				
				$c->$key = $value;
			}
			
			$result = null;
			
			if (is_callable(array($c, $action))) {
				$result = $c->$action($context);
			}
			
			// コントローラのパブリックな変数を設定
			$after = array();
			foreach ((array) $c as $key => $value) {
				if (!isset($before[$key]) or $before[$key] !== $value) {
					$after[$key] = $value;
				}
			}
			$context->apply($after);
			
			return $result;
		}
		
		return null;
	}
	
	// Renderer ================================
	
	function registerRenderer($renderer, $type) {
		if (! empty($this->_renderers[$type])) {
			unset($this->_renderers[$type]);
		}
		
		$this->_renderers[$type] =& $renderer;
	}
	
	function &rendererForType($type) {
		if (! empty($this->_renderers[$type])) {
			return $this->_renderers[$type];
		}
		
		return null;
	}
	
	// View ====================================
	
	function renderResponse(&$context) {
		$type = $context->type;
		
		if (!$type) {
			$type = 'html';
		}
		// 準備オッケー
		$this->_logger->log('will start rendering views: type='. $type);
		
		$renderer =& $this->rendererForType($type);
		if (! $renderer) {
			$this->_logger->log('cannot find renderer for type: '. $type);
			return;
		}
		
		$renderer->render($this, $context);
		
		$this->_logger->log('finished rendering views');
	}
}

class KioskApp extends Kiosk_App_App {
}

