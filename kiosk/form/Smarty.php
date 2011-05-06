<?php

class Kiosk_Form_Smarty {
	var $current_form = null;
	
	function register(&$smarty) {
		$smarty->register_block('form', array($this, 'form'));
		
		$functions = array(
			'input', 
			'hidden', 
			'password', 
			'radio', 
			'checkbox', 
			'submit', 
			'textarea', 
			'select', 
		);
		
		foreach ($functions as $name) {
			$smarty->register_function($name, array($this, $name));
		}
	}
	
	function form($params, $content, &$smarty, &$repeat) {
		if (isset($params['with'])) {
			$form = $this->_read($params, 'with');
			if (!is_a($form, "Kiosk_Form")) {
				trigger_error(KIOSK_ERROR_RUNTIME. "The object pass from 'with' parameter is not a subclass of Kiosk_Form.");
			}
		} else {
			$form = null;
		}
		
		if (is_null($content)) {
			$this->current_form = $form;
			return;
		}
		
		$html = Kiosk::util('HTML');
		
		if ($form) {
			$params += array(
				'name' => $form->name, 
				'method' => $form->method, 
				'action' => $form->action, 
			);
		}
		
		return $html->tag('form', $params, $content);
	}
	
	function _nameAndValue($smarty, &$params) {
		$name = $this->_read($params, 'name');
		
		if ($this->current_form) {
			$form = $this->current_form;
			
			$value = $form->value($name);
			
			$name = $form->name. '_'. $name;
		} else {
			$value = $smarty->get_template_vars($name);
		}
		
		if (is_null($value)) $value = '';
		
		return array($name, $value);
	}
	
	function input($params, &$smarty) {
		list($name, $value) = $this->_nameAndValue($smarty, $params);
		
		$html = Kiosk::util('HTML');
		
		$params += array(
			'type' => 'text', 
			'name' => $name, 
			'value' => $value, 
		);
		
		return $html->openTag('input', $params);
	}
	
	function hidden($params, &$smarty) {
		$params['type'] = 'hidden';
		
		return $this->input($params, $smarty);
	}
	
	function submit($params, &$smarty) {
		$params['type'] = 'submit';
		
		return $this->input($params, $smarty);
	}
	
	function password($params, &$smarty) {
		$params['type'] = 'password';
		$params['value'] = '';
		
		return $this->input($params, $smarty);
	}
	
	function radio($params, &$smarty) {
		list($name, $current) = $this->_nameAndValue($smarty, $params);
		
		if (isset($params['value'])) {
			if ($current == $params['value']) {
				$params['checked'] = true;
			}
		}
		
		$params['name'] = $name;
		$params['type'] = 'radio';
		
		$html = Kiosk::util('HTML');
		return $html->openTag('input', $params);
	}
	
	function checkbox($params, &$smarty) {
		list($name, $current) = $this->_nameAndValue($smarty, $params);
		
		$checked = false;
		
		if (is_bool($current)) {
			$checked = $current;
		} else if (isset($params['value']) and $current == $params['value']) {
			$checked = true;
		}
		
		$params['name'] = $name;
		$params['type'] = 'checkbox';
		$params['checked'] = $checked;
		
		$html = Kiosk::util('HTML');
		return $html->openTag('input', $params);
	}
	
	function textarea($params, &$smarty) {
		list($name, $value) = $this->_nameAndValue($smarty, $params);
		
		$html = Kiosk::util('HTML');
		
		$params['name'] = $name;
		
		return $html->tag('textarea', $params, $html->h($value));
	}
	
	function select($params, &$smarty) {
		list($name, $current) = $this->_nameAndValue($smarty, $params);
		
		$html = Kiosk::util('HTML');
		
		$unselected = $this->_read($params, 'unselected');
		$options = $this->_read($params, 'options');
		
		$params['name'] = $name;
		
		$str = $html->openTag('select', $params);
		
		if ($unselected) {
			$str .= $html->tag('option', array('value' => ''), $html->h($unselected));
		}
		
		if ($options) {
			foreach ((array) $options as $index=>$label) {
				$value = (is_integer($index) ? $label : $index);
				
				$opt_params = array('value' => $value);
				if ($value == $current) {
					$opt_params['selected'] = true;
				}
				
				$str .= $html->tag('option', $opt_params, $html->h($label));
			}
		}
		
		$str .= $html->closeTag('select');
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

