<?php

/*
	Part of Kiosk framework.
	
	Written by Yosuke "Basuke" Suzuki. @basuke
*/

/**
 *	URLからルーティングを行うためのクラス
 *	
 *	@access public
 */
class Kiosk_App_Router {
	var $_routes = array();
	
	/**
	 *	ルートパターンを設定する
	 *	
	 *	@param $pattern RilsスタイルのURLパターン
	 *	@param $defaults マッチするパターン以外で返す変数の値
	 *	@param $pattern パターンに現れる変数の実際に正規表現
	 *	@access pubic
	 */
	function map($pattern, $defaults=array(), $variables=array()) {
		$this->_routes[] = new Kiosk_App_Route($pattern, $defaults, $variables);
	}
	
	/**
	 *	URLを渡してマッチするルート情報を返す
	 *	
	 *	@param $url  マッチさせるURL文字列。
	 *	@return mixed マッチした値を収めた連想配列。どれともマッチしない場合はnull。
	 *	@access pubic
	 */
	function route($url) {
		foreach ($this->_routes as $route) {
			$result = $route->match($url);
			if ($result) return $result;
		}
		
		return null;
	}
	
	/**
	 *	routeの逆。URLを構成する値を元に、パターンにマッチするURLを返す。
	 *	
	 *	@param $params URLに必要な値を収めた連想配列。
	 *	@return string マッチした文字列。マッチしなければnull 
	 *	@access pubic
	 */
	function url($params) {
		foreach ($this->_routes as $route) {
			$url = $route->build($params);
			if ($url) return $url;
		}
		
		return null;
	}
}

/**
 *	ルート情報
 *	
 *	@access private
 */
class Kiosk_App_Route {
	var $_pattern;
	var $_defaults;
	var $_urlPattern;
	var $_variables;
	
	function Kiosk_App_Route($pattern, $defaults, $variables) {
		$this->__construct($pattern, $defaults, $variables);
	}
	
	function __construct($pattern, $defaults, $variables) {
		$this->_pattern = $pattern;
		$this->_defaults = $defaults;
		
		$this->_compile($pattern, $variables);
	}
	
	/**
	 *	ルート情報とマッチするか調べ、マッチした場合は抜き出した値を返す
	 *	
	 *	@param $url 調べるURL文字列
	 *	@return array マッチしたら連想配列。 マッチしなければnull
	 *	@access public
	 */
	function match($url) {
		if (!preg_match($this->_urlPattern, $url, $matches)) return;
		
		$params = array();
		foreach ($matches as $key=>$value) {
			if (is_integer($key)) continue;
			
			$params[$key] = $value;
		}
		return $params + $this->_defaults;
	}
	
	/**
	 *	ルート情報を構成する値を元にURLを構築する
	 *	
	 *	@param $params URLに必要な値を収めた連想配列。
	 *	@return string パターンにマッチしたらURL文字列。マッチしなければnull
	 *	@access public
	 */
	function build($params) {
		$url = $this->_pattern;
		
		foreach ($this->_defaults as $name => $value) {
			if (!isset($params[$name])) return null;
			if ($value != $params[$name]) return null;
			
			$url = str_replace(':'. $name, $value, $url);
			unset($params[$name]);
		}
		
		if (count($params) != count($this->_variable_patterns)) return null;
		
		foreach ($this->_variable_patterns as $name => $pattern) {
			if (!isset($params[$name])) return null;
			if (!preg_match($pattern, $params[$name])) return null;
			
			$url = str_replace(':'. $name, $params[$name], $url);
		}
		
		return $url;
	}
	
	function _compile($pattern, $variables) {
		if (preg_match_all('{:(\w+)}', $pattern, $matches)) {
			foreach ($matches[1] as $name) {
				if (!isset($variables[$name])) {
					$variables[$name] = '\\w+';
				}
			}
		}
		
		$patterns = array();
		$replaces = array();
		
		foreach ($variables as $name => $pat) {
			$patterns[] = '{:'. $name. '}';
			$replaces[] = "(?P<$name>$pat)";
			
			$variables[$name] = '{^'. $pat. '$}';
		}
		
		$pattern = preg_replace($patterns, $replaces, $pattern);
		
		$this->_urlPattern = '{^'. $pattern. '$}';
		$this->_variable_patterns = $variables;
	}
}

