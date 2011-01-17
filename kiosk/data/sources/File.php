<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';

class Kiosk_Data_Source_File extends Kiosk_Data_Source {
	/* static */
	function &open($config) {
		$source =& new Kiosk_Data_Source_File($config);
		return $source;
	}
	
	// schema creation
	
	function &buildSchema($class, $params) {
		$schema =& new Kiosk_Data_Source_File_Schema($class, $this, $params);
		return $schema;
	}
}

class Kiosk_Data_Source_File_Schema extends Kiosk_Data_Schema {
	var $path;		// 読み書きするファイルのパス
	var $columns;	// 各行のカラム名（オブジェクトのカラム名となる）
	
	/*
		オブジェクトを保存する
	*/
	function save(&$obj) {
		return $this->append($this->_buildLine($obj));
	}
	
	/*
		オブジェクトを検索する
	*/
	function findWithQuery(&$query) {
		$items = array();
		foreach ($this->readItems() as $columns) {
			$items[] = $columns;
		}
		
		return $items;
	}
	
	function rowToColumns($row, &$query) {
		return $row;
	}
	
	/*
		保存するための１行を作る
	*/
	function _buildLine($obj) {
		$items = array();
		
		foreach ($this->columns as $name) {
			$items[] = (empty($obj->$name) ? '' : strval($obj->$name));
		}
		
		return join(',', $items). "\n";
	}
	
	/*
		行を解析してハッシュを作る
	*/
	function _parseLine($line) {
		$columns = array();
		
		foreach (explode(",", $line) as $index=>$item) {
			if ($index >= count($this->columns)) break;
			$key = $this->columns[$index];
			$columns[$key] = $item;
		}
		
		return $columns;
	}
	
	// ファイル操作
	
	function append($data) {
		$fp = fopen($this->path, 'a');
		return fwrite($fp, $data);
	}
	
	function readItems() {
		$data = file_get_contents($this->path);
		$items = array();
		
		foreach (explode("\n", $data) as $line) {
			if ($line) {
				$items[] = $this->_parseLine($line);
			}
		}
		
		return $items;
	}
}

