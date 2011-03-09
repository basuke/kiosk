<?php

class Kiosk_Data_Schema {
	var $class;		// クラス名
	var $finalized;	// 正規化したか？
	var $afterLoad;	// ロード後に実行するフィルター
	
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
	
	function ensureQuery($query) {
		if (is_string($query)) {
			$query = array('conditions' => $query);
		}
		
		if (is_array($query)) {
			$query = $this->createQuery($query);
		}
		
		assert('is_a($query, "Kiosk_Data_Query")');
		
		return $query;
	}
	
	/*
		オブジェクトを検索する
	*/
	function find($query) {
		$query = $this->ensureQuery($query);
		$rows = $this->findWithQuery($query);
		
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
	
	function findWithQuery(&$query) {
		return array();
	}
	
	function count($query) {
		return $this->countWithQuery($this->ensureQuery($query));
	}
	
	function countWithQuery(&$query) {
		return 0;
	}
	
	function rowsToObjects($rows, &$query) {
		$objects = array();
		
		foreach ($rows as $key=>$row) {
			$columns = $this->rowToColumns($row, $query);
			if (is_null($columns)) continue;
			
			$objects[$key] = $this->createObject($columns);
		}
		
		return $objects;
	}
	
	function rowToColumns($row, &$query) {
		return $row;
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
	
	// カラム名に関するメソッド
	// native = データソース側
	// object = オブジェクト側
	
	function nativeColumnName($name) {
		if (isset($this->db_columns[$name])) {
			return $this->db_columns[$name];
		}
		
		$assoc = $this->associationWithName($name);
		if ($assoc and $assoc->hasColumnInOrigin()) {
			return $assoc->column;
		}
		
		if (in_array($name, array_keys($this->table->describe()))) {
			return $name;
		}
		
		return null;
	}
	
	function fullNativeColumnName($name) {
		$name = $this->nativeColumnName($name);
		if (!$name) return null;
		
		return $this->table->fullColumnName($name);
	}
	
	function resolveColumnNamePath($path, &$associations) {
		assert('is_array($path)');
		assert('count($path) >= 1');
		assert('is_array($associations)');
		
		$name = array_shift($path);
		if (empty($path)) {
			return $this->fullNativeColumnName($name);
		}
		
		$assoc =& $this->associationWithName($name);
		if (!$assoc) return false;
		
		$associations[$name] = $assoc;
		
		return $assoc->schema->resolveColumnNamePath($path, $associations);
	}
	
	function objectColumnName($name) {
		if (empty($this->obj_columns[$name])) return $name;
		
		return $this->obj_columns[$name];
	}
	
}

