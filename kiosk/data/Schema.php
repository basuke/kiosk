<?php

class Kiosk_Data_Schema {
	var $class;		// クラス名
	var $finalized;	// 正規化したか？
	
	function Kiosk_Data_Schema($class, &$source, $params) {
		$this->__construct($class, $source, $params);
	}
	
	function __construct($class, &$source, $params) {
		foreach (array_keys($params) as $key) {
			$this->$key =& $params[$key];
		}
		
		$this->class = $class;
		$this->source =& $source;
		$this->finalized = false;
	}
	
	/*
		スキーマ定義を完結させる
	*/
	function finalize() {
		if ($this->finalized) return;
		
		$this->finalized = true;
	}
	
	/*
		新規オブジェクトを作成する
	*/
	function createObject($columns = array()) {
		return array_to_object($columns, $this->class);
	}
	
	/*
		複数のオブジェクトを一括でインポートする
	*/
	function import($items) {
		$result = array();
		
		foreach ($items as $columns) {
			$obj = $this->createObject($columns);
			
			if ($obj->save()) {
				$result[] = $obj;
			} else {
				trigger_error(KIOSK_ERROR_RUNTIME. "failed to save object of class {$this->class}");
			}
		}
		
		return $result;
	}
	
	/*
		オブジェクトを保存する
	*/
	function save(&$obj) {
		return false;
	}
	
	/*
		オブジェクトを検索する
	*/
	function find($query) {
		if (is_string($query)) {
			$query = array('conditions' => $query);
		}
		
		if (is_array($query)) {
			$query = $this->createQuery($query);
		}
		
		assert('is_a($query, "Kiosk_Data_Query")');
		
		$rows = $query->fetch();
		
		if ($query->raw) {
			if ($query->first) {
				return array_first($rows);
			}
			
			return $rows;
		}
		
		$objects = $this->rowsToObjects($rows, $query);
		if ($this->afterLoad) {
			$this->applyFilter($objects, $this->afterLoad);
		}
		
		if ($query->first) {
			return array_first($objects);
		}
		
		return $objects;
	}
	
	function rowsToObjects($rows, &$query) {
		$objects = array();
		
		foreach ($rows as $key=>$row) {
			$columns = $this->rowToColumns($row, $query);
			$objects[$key] = $this->createObject($columns);
		}
		
		foreach ($this->refersTo as $assoc) {
			if (empty($assoc->load)) continue;
			
			$objects = $assoc->loadForObjects($objects);
		}
		
		return $objects;
	}
	
	function rowToColumns($row, &$query) {
		$columns = array();
		$values = array_values($row);
		
		foreach ($row as $key => $value) {
			if (strpos($key, '.') !== false) {
				$col = $query->parseColumn($key);
				$value = $col->valueForColumn($value);
				$key = $col->columnName();
			} else {
				$key = $this->objectColumnName($key);
			}
			$columns[$key] = $value;
		}
		
		return $columns;
	}
	
	// query の生成
	
	function queryClass() {
		return 'Kiosk_Data_Query';
	}
	
	function &createQuery($params = array()) {
		$class = $this->queryClass();
		$query =& new $class();
		
		$query->setSchema($this);
		$query->setParams($params);
		
		return $query;
	}
	
}

