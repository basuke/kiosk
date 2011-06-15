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
			$form = $params['with'];
			
			if (!is_a($form, "Kiosk_Form")) {
				trigger_error(KIOSK_ERROR_RUNTIME. "The object pass from 'with' parameter is not a subclass of Kiosk_Form.");
			}
			
			unset($params['with']);
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
	
	function input($params, &$smarty) {
		$reader =& $this->reader($params, $smarty);
		
		$html = Kiosk::util('HTML');
		return $html->openTag(
				'input', 
				$reader->params(array('value' => $reader->value(), 'type' => 'text')));
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
//		$params['value'] = '';
		
		return $this->input($params, $smarty);
	}
	
	function radio($params, &$smarty) {
		$params['type'] = 'radio';
		
		$reader =& $this->reader($params, $smarty);
		$name = $reader->name();
		$current = $reader->value();
		
		$checked = $reader->readAndClear('checked');
		
		if (isset($params['value'])) {
			$checked = ($current == $params['value']);
		}
		
		$html = Kiosk::util('HTML');
		return $html->openTag('input', $reader->params(compact('checked')));
	}
	
	function checkbox($params, &$smarty) {
		$params['type'] = 'checkbox';
		
		$reader =& $this->reader($params, $smarty);
		$name = $reader->name();
		$current = $reader->value();
		
		$checked = $reader->readAndClear('checked');
		
		if (is_bool($current)) {
			$checked = $current;
		} else if (isset($params['value']) and $current == $params['value']) {
			$checked = true;
		}
		
		$html = Kiosk::util('HTML');
		return $html->openTag('input', $reader->params(compact('checked')));
	}
	
	function textarea($params, &$smarty) {
		$reader =& $this->reader($params, $smarty);
		
		$html = Kiosk::util('HTML');
		return $html->tag('textarea', $reader->params(), $html->h($reader->value()));
	}
	
	function select($params, &$smarty) {
		$reader =& $this->reader($params, $smarty);
		$name = $reader->name();
		$current = $reader->value();
		
		$unselected = $reader->readAndClear('unselected');
		if (is_null($unselected)) {
			$unselected = $reader->fieldAttribute('unselected');
		}
		
		$options = $reader->readAndClear('options');
		if (is_null($options)) {
			$options = $reader->fieldAttribute('choices');
		}
		
		$html = Kiosk::util('HTML');
		$str = $html->openTag('select', $reader->params());
		
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
	
	function &reader(&$params, &$smarty) {
		$reader = new Kiosk_Form_Smarty_ParamReader();
		
		$reader->params = $params;
		$reader->smarty =& $smarty;
		$reader->form =& $this->current_form;
		
		// エラーを起こすなら最初に
		$reader->name();
		
		return $reader;
	}
}

class Kiosk_Form_Smarty_ParamReader {
	var $params;
	var $smarty;
	var $form;
	
	function name() {
		if (!isset($this->params['name'])) {
			trigger_error(KIOSK_ERROR_RUNTIME. "The tag has no name attribute.");
		}
		
		$name = $this->params['name'];
		
		if ($this->form) {
			$name = $this->form->name. '_'. $name;
		}
		
		return $name;
	}
	
	function value() {
		$name = $this->params['name'];
		
		if ($this->form) {
			$value = $this->form->value($name);
		} else {
			$value = $smarty->get_template_vars($name);
		}
		
		if (is_null($value)) $value = '';
		
		return $value;
	}
	
	function fieldAttribute($attr) {
		if ($this->form == null) return null;
		
		$name = $this->params['name'];
		
		$field =& $this->form->field($name);
		if ($field == null) return null;
		
		return isset($field->{$attr}) ? $field->{$attr} : null;
	}
	
	function readAndClear($name, $value = null) {
		if (isset($this->params[$name])) {
			$value = $this->params[$name];
			unset($this->params[$name]);
		}
		
		return $value;
	}
	
	function params($more = array()) {
		return array('name' => $this->name()) + $this->params + $more;
	}
}

