<?php

/*
	Kiosk App Router
*/

class Kiosk_App_Router {
	var $_routes = array();
	
	function map($pattern, $params=array(), $options=array()) {
		$this->_routes[] = new Kiosk_App_Route($pattern, $params, $options);
	}
	
	function route($url) {
		foreach ($this->_routes as $route) {
			$result = $route->match($url);
			if ($result) return $result;
		}
		
		return null;
	}
	
	function url($params) {
		foreach ($this->_routes as $route) {
			$url = $route->build($params);
			if ($url) return $url;
		}
		
		return null;
	}
}

class Kiosk_App_Route {
	function Kiosk_App_Route($pattern, $params, $options) {
		$this->__construct($pattern, $params, $options);
	}
	
	function __construct($pattern, $params, $options) {
		$this->_url = $pattern;
		$this->_compile($pattern, $options);
		$this->_params = $params;
	}
	
	function match($url) {
		if (!preg_match($this->_pattern, $url, $matches)) return;
		
		$params = array();
		foreach ($matches as $key=>$value) {
			if (is_integer($key)) continue;
			
			$params[$key] = $value;
		}
		return $params + $this->_params;
	}
	
	function build($params) {
		$url = $this->_url;
		
		foreach ($this->_params as $name => $value) {
			if (!isset($params[$name])) return null;
			if ($value != $params[$name]) return null;
			
			$url = str_replace(':'. $name, $value, $url);
			unset($params[$name]);
		}
		
		if (count($params) != count($this->_options)) return null;
		
		foreach ($this->_options as $name => $pattern) {
			if (!isset($params[$name])) return null;
			if (!preg_match($pattern, $params[$name])) return null;
			
			$url = str_replace(':'. $name, $params[$name], $url);
		}
		
		return $url;
	}
	
	function _compile($pattern, $options) {
		if (preg_match_all('|:(\w+)|', $pattern, $matches)) {
			foreach ($matches[1] as $name) {
				if (!isset($options[$name])) {
					$options[$name] = '\\w+';
				}
			}
		}
		
		$patterns = array();
		$replaces = array();
		
		foreach ($options as $name => $pat) {
			$patterns[] = '|:'. $name. '|';
			$replaces[] = "(?P<$name>$pat)";
			
			$options[$name] = '|^'. $pat. '$|';
		}
		
		$pattern = preg_replace($patterns, $replaces, $pattern);
		
		$this->_pattern = '|^'. $pattern. '$|';
		$this->_options = $options;
	}
}

