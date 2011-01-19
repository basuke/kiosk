<?php

require_once KIOSK_LIB_DIR. '/data/sources/DB.php';

class Kiosk_Data_Source_DB_SQLite extends Kiosk_Data_Source_DB {
	var $_types = array(
		'INTEGER' => 'integer', 
		'TEXT' => 'string', 
		'REAL' => 'float', 
		'TIMESTAMP' => 'timestamp', 
	);
	
	function language() {
		return new Kiosk_Data_DB_SQL_SQLite();
	}
	
	function connect() {
		extract($this->config);
		
		if (empty($db)) {
			$db = ':memory:';
		}
		
		$this->conn = sqlite_open($db);
	}
	
	function disconnect() {
		if ($this->conn) {
			sqlite_close($this->conn);
		}
	}
	
	function tables() {
		$rows = $this->fetchRows("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
		
		if (empty($rows)) {
			return array();
		}
		
		$tables = array();
		foreach ($rows as $row) {
			$tables[] = $row['name'];
		}
		
		return $tables;
	}
	
	function describe($table) {
		$columns = array();
		
		$sql = "PRAGMA table_info({$table})";
		foreach ($this->fetchRows($sql) as $info) {
			extract($info);
			
			if (isset($this->_types[$type])) {
				$type = $this->_types[$type];
			}
			
			$columns[$name]['name'] = $name;
			$columns[$name]['type'] = $type;
			$columns[$name]['notNull'] = (bool)$notnull;
			$columns[$name]['primaryKey'] = (bool)$pk;
		}
		
		return $columns;
	}
	
	function _query($sql) {
		return sqlite_query($this->conn, $sql);
	}
	
	function exec($sql) {
		$result = $this->query($sql);
		if (is_null($result)) return -1;
		
		return sqlite_changes($this->conn);
	}
	
	function lastId($table) {
		return sqlite_last_insert_rowid($this->conn);
	}
	
	function lasterror() {
		return sqlite_error_string($this->conn);
	}
	
	function count($sql) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		$count = sqlite_num_rows($result);
		
		return $count;
	}
	
	function fetchRows($sql, $assoc=true) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		$rows = array();
		$count = sqlite_num_rows($result);
		for ($row_no = 0; $row_no < $count; $row_no++) {
			$rows[] = $this->_fetch($result, $assoc);
		}
		
		return $rows;
	}
	
	function fetchOne($sql, $assoc=true) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		if (sqlite_num_rows($result)) {
			$row = $this->_fetch($result, $assoc);
		} else {
			$row = null;
		}
		
		return $row;
	}
	
	function _fetch($result, $assoc) {
		$row = array();
		
		foreach (sqlite_fetch_array($result, SQLITE_NUM) as $index=>$value) {
			if ($assoc) {
				$index = sqlite_field_name($result, $index);
			}
			
			$row[$index] = $value;
		}
		
		return $row;
	}
}

class Kiosk_Data_DB_SQL_SQLite extends Kiosk_Data_DB_SQL {
	function escape($str) {
		return sqlite_escape_string($str);
	}
	
	function literalBool($value) {
		return $value ? 1 : 0;
	}
}

