<?php

class Kiosk_App_Context {
	var $url = null;
	var $controller = null;
	var $action = 'index';
	var $type = 'html';
	var $layout = 'default';
	var $title = null;
	var $status = 200;
	var $redirect_url = null;
	
	function Kiosk_App_Context() {
		$this->__construct();
	}
	
	function __construct() {
		$this->url = empty($_GET['url']) ? null : $_GET['url'];
	}
	
	function apply($params) {
		foreach ($params as $key => $value) {
			$this->$key = $value;
		}
	}
	
	// Request
	
	function method() {
		return strtoupper($_SERVER['REQUEST_METHOD']);
	}
	
	function isPOST() {
		return $this->method() == 'POST';
	}
	
	function data() {
		return ($this->isPOST() ? $_POST : $_GET);
	}
	
	// Redirect
	
	function redirectTo($url, $temporally=true) {
		$this->redirect_url = $url;
		$this->status = ($temporally ? 302 : 301);
	}
	
	// HTTP Status
	
	function setHTTPStatus($status, $message) {
		$this->layout = 'error';
		
		if ($this->status != 200) return;
		
		$this->status = $status;
		$this->status_message = $message;
		
		$http = Kiosk::util('HTTP');
		$http->sendStatusHeader($status, $message);
	}
	
	// Response Rendering
	
	function dispatch() {
		$app =& Kiosk::app();
		
		$route = $app->route($this->url);
		if ($route) {
			// ルーティングの結果で情報をアップデート
			$this->apply($route);
			
			if (! $this->action) {
				$this->action = 'index';
			}
			
			// コントローラを実行
			if ($this->controller) {
				$result = $this->handleController();
				if ($result) {
					if ($this->type == 'json') {
						header('Content-Type: application/json');
						json_response($result);
						exit(0);
					}
					
					// コントローラの結果で情報をアップデート
					$this->apply($result);
				}
			}
		} else {
			$this->setHTTPStatus(404, 'File not found.');
		}
		
		// 準備オッケー
		Debug::log('will start rendering views');
		
		if ($this->redirect_url) {
			$http = Kiosk::util('HTTP');
			$http->sendRedirectHeader($this->redirect_url, $this->status == 302);
			exit(0);
		}
		
		$this->view = $app->viewPath($this);
		if (file_exists(APP_VIEWS_DIR. '/'. $this->view) == false) {
			Debug::log(LOG_ERR, "view file {$this->view} not found in views.");
			$this->setHTTPStatus(404, 'File not found');
		}
		
		$vars = (array) $this;
		
		if (file_exists(APP_VIEWS_DIR. "/layouts/{$this->layout}.html")) {
			$app->render("layouts/{$this->layout}.html", $vars);
		} else {
			$app->render($this->view, $vars);
			
			if (DEVELOPMENT) {
				$app->render('elements/debug_console.html', $vars);
			}
		}
		
		Debug::log('finished rendering views');
	}
	
	function handleController() {
		$app =& Kiosk::app();
		
		if (! $app->loadController($this->controller)) {
			return array();
		}
		
		return $app->runController($this);
	}
}

