<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';

class Kiosk_Data_Source_Mongo extends Kiosk_Data_Source {
	/* static */
	function &open($config) {
		$source =& new Kiosk_Data_Source_Mongo($config);
		return $source;
	}
	
	// schema creation
	
	function &buildSchema($class, $params) {
		$schema =& new Kiosk_Data_Source_Mongo_Schema($class, $this, $params);
		return $schema;
	}
}

class Kiosk_Data_Source_Mongo_Schema extends Kiosk_Data_Schema {
	/*
		オブジェクトを保存する
	*/
	function save(&$obj) {
	}
	
	/*
		オブジェクトを検索する
	*/
	function findWithQuery(&$query) {
		return array();
	}
	
	function rowToColumns($row, &$query) {
		return $row;
	}
}

