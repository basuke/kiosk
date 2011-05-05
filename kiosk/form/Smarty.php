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
		$form = $this->_read($params, 'with');
		
		if (is_null($content)) {
			$this->current_form = $form;
			return;
		}
		
		$html = Kiosk::util('HTML');
		
		$str = '';
		
		if ($form) {
			assert('is_a($form, "Kiosk_Form")');
			
			$params += array(
				'name' => $form->name, 
				'method' => $form->method, 
				'action' => $form->action, 
			);
		}
		
		return $html->tag('form', $params, $content);
	}
	
	function input($params, &$smarty) {
		$name = $this->_read($params, 'name');
		
		$html = Kiosk::util('HTML');
		
		$params += array(
			'type' => 'text', 
			'name' => $name, 
			'value' => $smarty->get_template_vars($name), 
		);
		
		$str = $html->openTag('input', $params);
		return $str;
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
		
		if (isset($params['name'])) {
			$smarty->assign($params['name'], '');
		}
		
		return $this->input($params, $smarty);
	}
	
	function radio($params, &$smarty) {
		$name = $this->_read($params, 'name');
		
		if (isset($params['value'])) {
			$current = $smarty->get_template_vars($name);
			if ($current == $params['value']) {
				$params['checked'] = true;
			}
		}
		
		$params['name'] = $name;
		$params['type'] = 'radio';
		
		return $this->input($params, $smarty);
	}
	
	function checkbox($params, &$smarty) {
		$name = $this->_read($params, 'name');
		
		$checked = false;
		
		$current = $smarty->get_template_vars($name);
		if (is_bool($current)) {
			$checked = $current;
		} else if (isset($params['value']) and $current == $params['value']) {
			$checked = true;
		}
		
		$params['name'] = $name;
		$params['type'] = 'checkbox';
		if ($checked) {
			$params['checked'] = true;
		} else {
			unset($params['checked']);
		}
		
		return $this->input($params, $smarty);
	}
	
	function textarea($params, &$smarty) {
		$html = Kiosk::util('HTML');
		
		if (isset($params['name'])) {
			$value = $smarty->get_template_vars($params['name']);
		} else if (isset($params['value'])) {
			$value = $params['value'];
		} else {
			$value = '';
		}
		
		return $html->tag('textarea', $params, $html->h($value));
	}
	
	function select($params, &$smarty) {
		$html = Kiosk::util('HTML');
		
		if (isset($params['name'])) {
			$current = $smarty->get_template_vars($params['name']);
		} else {
			$current = null;
		}
		
		$unselected = $this->_read($params, 'unselected');
		$options = $this->_read($params, 'options');
		
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

