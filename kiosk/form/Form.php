<?php

class Kiosk_Form {
	var $errors = array();
	var $data = null;
	var $files = null;
	var $html = null;
	
	// フィールドの定義
	var $fields = array();
	
	function Kiosk_Form($fields=array()) {
		$this->__construct($fields);
	}
	
	function __construct($fields=array()) {
		assert('is_array($fields)');
		
		$this->html = Kiosk::util('HTML');
		
		foreach ($fields as $name => $def) {
			$this->addField($name, $def);
		}
	}
	
	// definition ==============================
	
	/*
	'email' => array(
		'type' => 'string' / 'integer' / 'boolean' / ...
		'lable' => 'e-mail address'
		'required' => true, 
		'initial' => 'hello', 
	)
	*/
	function addField($name, $def) {
		$field = Kiosk_Form_Field::create($name, $def);
		$this->fields[$name] = $field;
	}
	
	// data handling ===========================
	
	function bind($data, $files=null) {
		assert('$this->isBound() == false');
		
		$this->data = $data;
		$this->files = $files;
	}
	
	function isBound() {
		return ! is_null($this->data);
	}
	
	function isValid() {
	}
	
	function initial($data = null) {
		$data = array();
		
		foreach ($this->fields as $field) {
			$data[$field->name] = $field->initial();
		}
		
		return $data;
	}
	
	function value($name) {
		$key = null;
		
		$pos1 = strpos($name, '[');
		$pos2 = strpos($name, ']');
		
		if ($pos1 !== false and $pos2 !== false) {
			$key = substr($name, $pos1 + 1, ($pos2 - $pos1 - 1));
			$name = substr($name, 0, $pos1);
			
			if (ctype_digit($key)) {
				$key = intval($key);
			}
		}
		
		$value = null;
		
		if (isset($this->data[$name])) {
			$value = $this->data[$name];
			
			if ($key and is_array($value)) {
				$value = $value[$key];
			}
		}
		
		return $value;
	}
	
	function resolvePath($root, $path) {
		// counts[abc]
		$value = $root;
		
		while ($path) {
			if (preg_match('/^([^\\[]]+)(?:\\[([^\\]]+)\\]$/', $matches)) {
				$key = $matches[1];
				
				if (! is_array($value)) return null;
				if (! isset($value[$key])) return null;
				
				$value = $value[$key];
			} else {
				return null;
			}
			
			$path = $matches[2];
		}
		
		return $value;
	}
	
	// form html rendering =====================
	
	function start($name, $options=array()) {
		$method = 'post';
		
		extract($options);
		
		$attributes = $options + array(
			'name' => $name, 
			'method' => 'post', 
		);
		
		return $this->html->openTag('form', $attributes);
	}
	
	function finish() {
		return '</form>';
	}
	
	function input($name, $options=array()) {
		$options += array(
			'type' => 'text', 
			'name' => $name, 
			'value' => strval($this->value($name)), 
		);
		
		$str = $this->html->openTag('input', $options);
		return $str;
	}
	
	function password($name, $options=array()) {
	}
	
	function hidden($name, $value=null, $options=array()) {
	}
	
	function textarea($name, $options=array()) {
		return '<textarea name="xxx"></textarea>';
	}
	
	function checkbox($name, $options=array()) {
	}
	
	function radio($name, $options=array()) {
		$options += array(
			'type' => 'radio', 
			'name' => $name, 
			'value' => 'on', 
		);
		
		if ($this->value($name) == $options['value']) {
			$options[] = 'checked';
		}
		
		$str = $this->html->openTag('input', $options);
		return $str;
	}
	
	function select($name, $options=array()) {
	}
	
	function option($value, $options=array()) {
	}
	
	function submit($label, $options=array()) {
	}
}

class Kiosk_Form_Field {
	var $name;
	var $required = false;
	var $array = false;
	var $label = '';
	var $initial = '';
	var $help_text = '';
	var $error_messages = array();
	var $validators = array();
	
	function create($name, $def) {
		$field = new Kiosk_Form_TextField($name, $def);
		
		return $field;
	}
	
	function Kiosk_Form_Field($name, $def) {
		$this->__construct($name, $def);
	}
	
	function __construct($name, $def) {
		$this->name = $name;
		
		foreach ($def as $name => $value) {
			$this->$name = $value;
		}
	}
	
	function initial() {
		if ($this->array) {
			return array();
		}
		
		return $this->initial;
	}
	
	function render(&$form) {
	}
}

// Text

class Kiosk_Form_TextField extends Kiosk_Form_Field {
	var $regex;
	
	function render(&$form) {
	}
}

class Kiosk_Form_EmailField extends Kiosk_Form_TextField {
	function render(&$form) {
	}
}

class Kiosk_Form_URLField extends Kiosk_Form_TextField {
	function render(&$form) {
	}
}

class Kiosk_Form_SlugField extends Kiosk_Form_TextField {
	function render(&$form) {
	}
}

class Kiosk_Form_PasswordField extends Kiosk_Form_TextField {
	function render(&$form) {
	}
}

// Number

class Kiosk_Form_NumberField extends Kiosk_Form_Field {
	function render(&$form) {
	}
}

class Kiosk_Form_IntegerField extends Kiosk_Form_NumberField {
	function render(&$form) {
	}
}

class Kiosk_Form_FloatField extends Kiosk_Form_NumberField {
	function render(&$form) {
	}
}

class Kiosk_Form_DecimalField extends Kiosk_Form_NumberField {
	function render(&$form) {
	}
}

class Kiosk_Form_BooleanField extends Kiosk_Form_NumberField {
	function render(&$form) {
	}
}

// File

class Kiosk_Form_FileField extends Kiosk_Form_Field {
	function render(&$form) {
	}
}

class Kiosk_Form_ImageFileField extends Kiosk_Form_FileField {
	function render(&$form) {
	}
}


/*

$form = $app->form(array(
	'name' => array('text', 'required', 'label' => 'Name'), 
	'profile' => array('text', 'widget' => 'textarea', 'label' => 'Profile'), 
	'email' => array('email', 'required', 'label' => 'Name'), 
	'name' => array('url', 'label' => 'Name'), 
));

type			
	text
	string
	email
	url
	slug
	integer
	float
	decimal
	date
	datetime
	time
	password
	boolean
	choice
	select
	file
	image

attribute
	name
	required
	label
	initial
	help_text
	error_messages
	validators

*/
