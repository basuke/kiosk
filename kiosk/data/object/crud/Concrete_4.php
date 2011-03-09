<?php

require_once KIOSK_LIB_DIR. '/utils/magic.php';

// PHP 4用のKioskのインターフェース

class Kiosk_Object_CRUD_Concrete extends Kiosk_Object_CRUD {
	function bind(&$db, $desc = array()) {
		$class_info = get_function_call_info();
		$class = $class_info->get_class();
		
//		overload($class);
		Kiosk_bind($class, $db, $desc);
	}
	
	function import($items) {
		$info = get_function_call_info();
		$class = $info->get_class();
		
		$args = func_get_args();
		return Kiosk_import($class, $args);
	}
	
	function create($columns=array()) {
		$info = get_function_call_info();
		$class = $info->get_class();
		
		return Kiosk_create($class, $columns);
	}
	
	function load($id, $params=array()) {
		$info = get_function_call_info();
		$class = $info->get_class();
		
		return Kiosk_load($class, $id, $params);
	}
	
	function find($params=array()) {
		$info = get_function_call_info();
		$class = $info->get_class();
		
		return Kiosk_find($class, $params);
	}
	
	function count($params=array()) {
		$class = get_function_call_info();
		return Kiosk_count($class, $params);
	}
}

