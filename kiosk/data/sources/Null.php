<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';

class Kiosk_Data_Source_Null extends Kiosk_Data_Source {
	/* static */
	function &open($config) {
		$source =& new Kiosk_Data_Source_Null($config);
		return $source;
	}
	
	// schema creation
	
	function &buildSchema($class, $params) {
		extract($params, EXTR_SKIP);
		
		$schema =& new Kiosk_Schema();
		foreach ($params as $key=>$value) {
			$schema->$key = $value;
		}
		
		return $schema;
	}
}

