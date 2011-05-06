<?php

define('KioskFormErrorRequired', 'required');
define('KioskFormErrorInvalid', 'invalid');
define('KioskFormErrorNotMatch', 'not_match');
define('KioskFormErrorOutOfRange', 'out_of_range');

class Kiosk_Form {
	var $name = 'form';
	var $action = '';
	var $method = 'POST';
	var $errors = null;
	var $data = null;
	var $raw_data = null;
	var $files = null;
	var $version = null;
	
	// フィールドの定義
	var $fields = array();
	
	function Kiosk_Form($fields=array()) {
		$this->__construct($fields);
	}
	
	function __construct($fields=array()) {
		assert('is_array($fields)');
		
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
		
		$this->fields[$name] =& $field;
	}
	
	function &field($name) {
		if (!isset($this->fields[$name])) {
			trigger_error(KIOSK_ERROR_RUNTIME. "There's no field with name '$name'.");
		}
		
		return $this->fields[$name];
	}
	
	// data handling ===========================
	
	function bind($data, $files=null) {
		assert('$this->isBound() == false');
		assert('is_array($data)');
		
		$data = $this->_filterData($data);
		
		if ($files) {
			$data += $this->_filterData($files);
		}
		
		$this->beforeBind($data);
		
		$this->_validate($data);
		
		$this->afterBind($data);
		
		Debug::log('Form data: '. var_export($this->data, true));
		
		if ($this->errors) {
			Debug::log(LOG_ERR, 'Form error: '. var_export($this->errors, true));
		}
	}
	
	function isBound() {
		return ! is_null($this->data);
	}
	
	function isValid() {
		return empty($this->errors);
	}
	
	function initial() {
		$data = array();
		
		foreach (array_keys($this->fields) as $name) {
			$data[$name] = $this->fields[$name]->initial();
		}
		
		return $data;
	}
	
	function data() {
		if ($this->isBound()) {
			return $this->data;
		}
		
		return $this->initial();
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
		} else {
			$initial = $this->initial();
			if (isset($initial[$name])) {
				$value = $initial[$name];
			}
		}
		
		if ($key and is_array($value)) {
			$value = $value[$key];
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
	
	function _filterData($data) {
		$regex = '/^'. $this->name. '_(.+)$/i';
		
		$filtered = array();
		foreach ($data as $name => $value) {
			if (preg_match($regex, $name, $match)) {
				$filtered[$match[1]] = $value;
			}
		}
		
		return $filtered;
	}
	
	// validation
	
	function addError($field, $error, $message = null) {
		if (empty($this->errors[$field])) {
			$this->errors[$field] = array();
		}
		
		if (empty($message)) {
			$message = $this->errorMessageFor($field, $error);
		}
		
		$this->errors[$field][$error] = $message;
	}
	
	function fieldError($field) {
		if (empty($this->errors[$field])) return false;
		
		return $this->errors[$field];
	}
	
	function errorMessageFor($field, $error) {
		return $error;
	}
	
	function _validate($data) {
		assert('is_null($this->errors)');
		
		$this->errors = array();
		
		$this->data = array();
		
		foreach (array_keys($this->fields) as $name) {
			$value = isset($data[$name]) ? $data[$name] : null;
			$this->fields[$name]->clean($this, $value);
			$this->data[$name] = $value;
		}
		
		$this->beforeValidate();
		
		foreach (array_keys($this->fields) as $name) {
			if (!$this->fieldError($name)) {
				$value = $this->data[$name];
				$this->fields[$name]->validate($this, $value);
			}
		}
		
		$this->afterValidate();
	}
	
	// versioning
	
	function version() {
		$this->version;
	}
	
	function sameVersion($data_version) {
		if (is_null($this->version)) {
			return is_null($data_version);
		}
		
		return $this->version === $data_version;
	}
	
	// callbacks
	
	function beforeBind($data) {
	}
	
	function afterBind($data) {
	}
	
	function beforeValidate() {
	}
	
	function afterValidate() {
	}
	
}

class Kiosk_Form_Field {
	var $name;
	var $required = false;
	var $array = false;
	var $label = '';
	var $initial = null;
	var $help_text = '';
	
	function create($name, $def) {
		$class = 'Kiosk_Form_TextField';
		
		$type = isset($def['type']) ? $def['type'] : 'string';
		unset($def['type']);
		
		switch (strtoupper($type)) {
			case 'BOOL':
			case 'FLAG':
			case 'BOOLEAN':
				$class = 'Kiosk_Form_BooleanField';
				break;
				
			case 'EMAIL':
				$class = 'Kiosk_Form_EmailField';
				break;
		}
		
		$field = new $class($name, $def);
		
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
		if (! is_null($this->initial)) {
			return $this->initial;
		}
		
		return '';
	}
	
	function clean(&$form, &$value) {
		$error = $this->cleanValue($value);
		if ($error) {
			$form->addError($this->name, $error);
			return;
		}
	}
	
	function validate(&$form, $value) {
		if ($this->required and $this->isEmptyValue($value)) {
			$form->addError($this->name, KioskFormErrorRequired);
			return;
		}
		
		$error = $this->validateValue($form, $value);
		if ($error) {
			$form->addError($this->name, $error);
			return;
		}
	}
	
	function cleanValue(&$value) {
		
	}
	
	function validateValue(&$form, $value) {
		if (!empty($this->confirmation)) {
			$field_name = $this->confirmation;
			
			if (!$form->fieldError($field_name)) {
				$field_value = $form->value($field_name);
				if ($field_value != $value) {
					return KioskFormErrorNotMatch;
				}
			}
		}
	}
	
	function isEmptyValue($value) {
		return strval($value) == '';
	}
	
	// utility
	
	function normalizeString($value) {
		$value = mb_convert_kana($value, 'KVa');
		$value = trim($value);
		return $value;
	}
}

// Text

class Kiosk_Form_TextField extends Kiosk_Form_Field {
	var $regex;
	
	function validateValue(&$form, $value) {
		$error = parent::validateValue($form, $value);
		if ($error) {
			return $error;
		}
		
		
	}
}

class Kiosk_Form_EmailField extends Kiosk_Form_TextField {
	function cleanValue(&$value) {
		$value = $this->normalizeString($value);
		if (!$this->isEmptyValue($value)) {
			
			$regex = '/^[_a-z0-9-]+[_.a-z0-9-]*@([a-z0-9-]+(\.[a-z0-9-]+)+)$/i';
			if (!preg_match($regex, $value)) {
				return KioskFormErrorInvalid;
			}
		}
	}
}

class Kiosk_Form_URLField extends Kiosk_Form_TextField {
}

class Kiosk_Form_SlugField extends Kiosk_Form_TextField {
}

class Kiosk_Form_PasswordField extends Kiosk_Form_TextField {
}

// Number

class Kiosk_Form_NumberField extends Kiosk_Form_TextField {
}

class Kiosk_Form_IntegerField extends Kiosk_Form_NumberField {
}

class Kiosk_Form_FloatField extends Kiosk_Form_NumberField {
}

class Kiosk_Form_DecimalField extends Kiosk_Form_NumberField {
}

class Kiosk_Form_BooleanField extends Kiosk_Form_NumberField {
	var $initial = false;
	
	function cleanValue(&$value) {
		if (is_bool($value)) {
		} else if (is_integer($value)) {
			$value = ($value != 0);
		} else if (is_string($value)) {
			$value = $this->normalizeString($value);
			
			if (ctype_digit($value)) {
				$value = (intval($value) != 0);
			} else {
				$value = !in_array(strtoupper($value), array('OFF', 'NO', 'FALSE'));
			}
		} else if (is_null($value)) {
			$value = false;
		} else {
			return KioskFormErrorInvalid;
		}
	}
}

// File

class Kiosk_Form_FileField extends Kiosk_Form_Field {
}

class Kiosk_Form_ImageFileField extends Kiosk_Form_FileField {
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
