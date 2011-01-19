<?php

class Kiosk_App_Context {
	var $url = null;
	var $controller = null;
	var $action = 'index';
	var $type = 'html';
	var $layout = 'default';
	var $title = null;
	var $status = 200;
	
	var $_redirect_url = null;
	var $_route_result = null;
	var $_controller_result = null;
	
	function Kiosk_App_Context() {
		$this->__construct();
	}
	
	function __construct() {
		$this->url = empty($_GET['url']) ? null : $_GET['url'];
	}
	
	function apply($params) {
		$applied = array();
		
		foreach ($params as $key => $value) {
			if (! preg_match('/^\\w+$/', $key)) continue;
			
			$this->$key = $value;
			$applied[$key] = $value;
		}
		
		return $applied;
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
	
	// Result
	
	function routeResult() {
		return $this->_route_result;
	}
	
	function setRouteResult($result) {
		$this->_route_result = $this->apply($result);
	}
	
	function controllerResult() {
		return $this->_controller_result;
	}
	
	function setControllerResult($result) {
		$this->_controller_result = $this->apply($result);
	}
	
	function variables() {
		$vars = array();
		
		foreach ((array) $this as $key=>$value) {
			if ($key[0] == '_') continue;
			$vars[$key] = $value;
		}
		
		return $vars;
	}
	
	// Redirect
	
	function redirectTo($url, $temporally=true) {
		$this->_redirect_url = $url;
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
		$app->route($this->url, $this);
		
		if ($this->_redirect_url) {
			$http = Kiosk::util('HTTP');
			$http->sendRedirectHeader($this->_redirect_url, $this->status == 302);
			exit(0);
		}
		
		$app->renderResponse($this);
	}
}

