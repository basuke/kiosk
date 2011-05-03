<?php

// PHP 5用のKioskの実態

class Kiosk_Object_CRUD_Concrete extends Kiosk_Object_CRUD {
	static public function bind($source, $desc = array()) {
		$class = get_called_class();
		
		if (is_string($source)) {
			$source = Kiosk_data()->source($source);
		}
		
		if (Kiosk_isClass($class, 'Kiosk')) {
			$descs = func_get_args();
			array_shift($descs);
			
			foreach ($descs as $desc) {
				$class = $desc['class'];
				if (!$class) {
					return trigger_error(KIOSK_ERROR_CONFIG. "no class specified");
				}
				
				Kiosk_bind($class, $source, $desc);
			}
		} else {
			Kiosk_bind($class, $source, $desc);
		}
	}
	
	static public function import($items) {
		$class = get_called_class();
		$args = func_get_args();
		return Kiosk_import($class, $args);
	}
	
	static public function create($columns=array()) {
		$class = get_called_class();
		return Kiosk_create($class, $columns);
	}
	
	static public function load($id, $params=array()) {
		$class = get_called_class();
		return Kiosk_load($class, $id, $params);
	}
	
	static public function find($params=array()) {
		$class = get_called_class();
		return Kiosk_find($class, $params);
	}
	
	static public function count($params=array()) {
		$class = get_called_class();
		return Kiosk_count($class, $params);
	}
}

