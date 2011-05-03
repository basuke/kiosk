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
			if (! preg_match('/^[^_]\\w*$/', $key)) continue;
			
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
	
	/**
	 *	$this->paramsから指定の値を読み取ってハッシュで返す。
	 *	定義されていなければnullを設定する
	 *	$namesにハッシュを渡せば、$paramsから読み取る変数名と
	 *	定義されるハッシュのキーを変えることが出来る
	 *	
	 *	$params = $this->params('url', array(
	 *				'foo', 				// 'foo' => $this->params['url']['foo']
	 *				'hoge' => 'bar', 	// 'bar' => $this->params['url']['hoge']
	 *				...
	 *	));
	 *
	 *	@param $kind $this->params[$kind][...] の指定
	 *	@return 指定の名前のキーを持つハッシュ
	 *	@access protected
	 */
	function collect($data, $names /* , $name2, $name3, ... */) {
		if (!is_array($names)) {
			$names = func_get_args();
			array_shift($names);
		}
		
		$result = array();
		
		foreach ($names as $name => $var_name) {
			if (is_integer($name)) {
				$name = $var_name;
			}
			
			$result[$var_name] = isset($data[$name]) ? $data[$name] : null;
		}
		
		return $result;
	}
	
	// Result
	
	function routeResult() {
		return $this->_route_result;
	}
	
	function setRouteResult($result) {
		$this->_route_result = $result;
	}
	
	function controllerResult() {
		return $this->_controller_result;
	}
	
	function setControllerResult($result) {
		$this->_controller_result = $result;
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
	
	// Content Type
	
	function type() {
		return $this->type;
	}
	
	function setType($type) {
		$this->type = $type;
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

