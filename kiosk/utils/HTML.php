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
	
	function q($str) {
		$str = htmlspecialchars($str);
		if (! preg_match('/^[a-zA-Z0-9_]+$/', $str)) {
			$str = '"'. $str. '"';
		}
		return $str;
	}
	
	function h($str) {
		return htmlspecialchars($str);
	}
}
