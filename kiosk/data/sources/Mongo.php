<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';

class Kiosk_Data_Source_Mongo extends Kiosk_Data_Source {
	/* static */
	function &open($config) {
		$source = new Kiosk_Data_Source_Mongo($config);
		return $source;
	}
	
	var $conn;
	var $db;
	
	function __construct($config) {
		$host = 'localhost';
		$port = 13174;
		$dbname = null;
		extract($config);
		
		$this->conn = new Mongo($host, $port);
		$this->db = $this->conn->$dbname;
	}
	
	// schema creation
	
	function buildSchema($class, $params) {
		extract($params, EXTR_SKIP);
		
		if (empty($name)) {
			$namer = Kiosk::namer();
			$params['name'] = $namer->classNameToTableName($class);
		}
		
		$schema = new Kiosk_Data_Source_Mongo_Schema($class, $this, $params);
		return $schema;
	}
}

class Kiosk_Data_Source_Mongo_Schema extends Kiosk_Data_Schema {
	var $collection;
	
	function __construct($class, $source, $params) {
		parent::__construct($class, $source, $params);
		
		$name = null;
		extract($params);
		
		$this->collection = $source->db->$name;
	}
	
	/*
		オブジェクトを保存する
	*/
	function save($obj) {
	}
	
	function createQuery($params) {
		$query = parent::createQuery($params);
		return $query;
	}
	
	/*
		オブジェクトを検索する
	*/
	function findWithQuery($query) {
		$conditions = $query->conditions;
		if (! $conditions) $conditions = array();
		
		$columns = $query->columns;
		if (! $columns) $columns = array();
		
		$cursor = $this->collection->find($conditions, $columns);
		
		return iterator_to_array($cursor, false);
	}
	
	function rowToColumns($row, $query) {
		return $row;
	}
}

