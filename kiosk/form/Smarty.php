<?php

class Kiosk_Form_Smarty {
	var $current_form = null;
	
	function register(&$smarty) {
		$smarty->register_block('form', array($this, 'form'));
		
		$functions = array(
			'input', 
			'hidden', 
			'radio', 
			'textarea', 
		);
		
		foreach ($functions as $name) {
			$smarty->register_function($name, array($this, $name));
		}
	}
	
	function form($params, $content, &$smarty, &$repeat) {
		$form = $this->_read($params, 'with');
		
		if (is_null($content)) {
			$this->current_form = $form;
			return;
		}
		
		$html = Kiosk::util('HTML');
		
		$str = '';
		
		if ($form) {
			$name = $this->_read($params, 'name', 'unknown');
			
			assert('is_a($form, "Kiosk_Form")');
			
			$str = $form->start($name, $params);
		} else {
			$str = $html->openTag('form', $params);
		}
		
		$str .= $content. $html->closeTag('form');
		
		return $str;
	}
	
	function input($params, &$smarty) {
		if ($this->current_form) {
			$name = $this->_read($params, 'name');
			if ($name) {
				return $this->current_form->input($name, $params);
			}
		}
		
		$html = Kiosk::util('HTML');
		
		$params += array(
			'type' => 'text', 
			'name' => $name, 
		);
		
		$str = $html->openTag('input', $params);
		return $str;
	}
	
	function hidden($params, &$smarty) {
		$params['type'] = 'hidden';
		
		return $this->input($params, $smarty);
	}
	
	function radio($params, &$smarty) {
		if ($this->current_form) {
			$name = $this->_read($params, 'name');
			if ($name) {
				return $this->current_form->radio($name, $params);
			}
		}
		
		$params['type'] = 'radio';
		
		return $this->input($params, $smarty);
	}
	
	function textarea($params, &$smarty) {
		$html = Kiosk::util('HTML');
		
		$str = $html->openTag('textarea', $params);
		$str .= $html->h('');
		$str .= $html->closeTag('textarea');
		return $str;
	}
	
	function _read(&$params, $name, $value = null) {
		if (isset($params[$name])) {
			$value = $params[$name];
			unset($params[$name]);
		}
		
		return $value;
	}
}

