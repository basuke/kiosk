<?php

class Kiosk_Form {
	var $errors = array();
	var $fields = array();
	
	function Kiosk_Form($fields=array()) {
		$this->__construct($fields);
	}
	
	function __construct($fields=array()) {
	}
	
	function bind($data, $files=null) {
	}
	
	function initial($data) {
	}
	
	function isBound() {
	}
	
	function isValid() {
	}
	
	function start($attributes=null) {
	}
	
	function finish() {
	}
	
	function input($name) {
	}
	
	function password($name) {
	}
	
	function hidden($name, $value=null) {
	}
	
	function textarea($name) {
	}
	
	function checkbox($name) {
	}
	
	function radio($name) {
	}
	
	function select($name) {
	}
	
	function option($value) {
	}
	
	function submit($label) {
	}
}

class Kiosk_Form_Field {
	var $name;
	var $required;
	var $label;
	var $initial;
	var $help_text;
	var $error_messages = array();
	var $validators = array();
	
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Input extends Kiosk_Form_Field {
	function render(&$form) {
	}
	
	function tag() {
	}
}

class Kiosk_Form_Field_Text extends Kiosk_Form_Field_Input {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Email extends Kiosk_Form_Field_Input {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_URL extends Kiosk_Form_Field_Input {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Slug extends Kiosk_Form_Field_Input {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Number extends Kiosk_Form_Field_Input {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Integer extends Kiosk_Form_Field_Number {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Float extends Kiosk_Form_Field_Number {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Decimal extends Kiosk_Form_Field_Number {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Password extends Kiosk_Form_Field_Input {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Boolean extends Kiosk_Form_Field {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Choice extends Kiosk_Form_Field {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Select extends Kiosk_Form_Field_Choice {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_File extends Kiosk_Form_Field {
	function render(&$form) {
	}
}

class Kiosk_Form_Field_Image extends Kiosk_Form_Field_File {
	function render(&$form) {
	}
}


$form = $app->form(array(
	'name' => array('text', 'required', 'label' => 'Name'), 
	'profile' => array('text', 'widget' => 'textarea', 'label' => 'Profile'), 
	'email' => array('email', 'required', 'label' => 'Name'), 
	'name' => array('url', 'label' => 'Name'), 
));

/*

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

