<?php

/**
 *	HTMLに関する処理を実装するクラス
 *	
 *	@access public
 */

class Kiosk_Utils_HTML {
	function openTag($tag, $attributes=array()) {
		$tag = '<'. $tag;
		
		foreach ($attributes as $name=>$value) {
			$value = $this->q($value);
			
			if (is_integer($name)) {
				$tag .= ' '. $value;
			} else if ($value === true) {
				$tag .= ' '. $this->q($name);
			} else if ($value === false) {
				//
			} else {
				$tag .= ' '. $this->q($name). '='. $value;
			}
		}
		
		$tag .= '>';
		
		return $tag;
	}
	
	function closeTag($tag) {
		return '</'. $tag. '>';
	}
	
	function tag($tag, $attributes, $contents) {
		return $this->openTag($tag, $attributes). $contents. $this->closeTag($tag);
	}
	
	function q($str) {
		$str = htmlspecialchars(strval($str));
		if (! preg_match('/^[a-zA-Z0-9_]+$/', $str)) {
			$str = '"'. $str. '"';
		}
		return $str;
	}
	
	function h($str) {
		return htmlspecialchars(strval($str));
	}
}
