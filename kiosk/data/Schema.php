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
	function find($params) {
		return array();
	}
	
}

