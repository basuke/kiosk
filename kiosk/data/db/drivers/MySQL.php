<?php

require_once dirname(dirname(__FILE__)). '/Driver.php';

class Kiosk_DB_Driver_MySQL extends Kiosk_DB_Driver {
	var $_types = array(
		'int' => 'integer', 
		'text' => 'string',
		'float' => 'float', 
		
		'timestamp' => 'timestamp', 
	);
	
	function language() {
		return new Kiosk_Data_DB_SQL_MySQL();
	}
	
	function connect() {
		extract($this->config);
		
		if (!empty($port)) {
			$host .= ':' . $port;
		}
		
		$this->conn = mysql_connect($host, $user, $password);
		if ($this->conn) {
			mysql_select_db($db, $this->conn);
		}
	}
	
	function disconnect() {
		if ($this->conn) {
			mysql_close($this->conn);
		}
	}
	
	function tables() {
		$rows = $this->fetchRows('SHOW TABLES FROM ' . $this->config['db'], false);
		if (empty($rows)) {
			return array();
		}
		$tables = array();
		foreach ($rows as $row) {
			$tables[] = $row[0];
		}
		return $tables;
	}
	
	function describe($table) {
		$columns = array();
		
		$sql = "SHOW FULL COLUMNS FROM {$table}";
		foreach ($this->fetchRows($sql) as $info) {
			$name = $info['Field'];
			
			$type = preg_replace('/[(].+$/', '', $info['Type']);
			if (isset($this->_types[$type])) {
				$type = $this->_types[$type];
			}
			
			$columns[$name]['name'] = $name;
			$columns[$name]['type'] = $type;
			$columns[$name]['notNull'] = ($info['Null'] == 'NO');
			$columns[$name]['primaryKey'] = ($info['Key'] == 'PRI');
		}
		
		return $columns;
	}
	
	function _query($sql) {
		return mysql_query($sql, $this->conn);
	}
	
	function exec($sql) {
		$result = $this->query($sql);
		if (is_null($result)) return -1;
		
		$count = mysql_affected_rows($this->conn);
		if (is_resource($result)) {
			mysql_free_result($result);
		}
		return $count;
	}
	
	function lastId($table) {
		return mysql_insert_id($this->conn);
	}
	
	function lasterror() {
		return mysql_error($this->conn);
	}
	
	function count($sql) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		$count = mysql_num_rows($result);
		mysql_free_result($result);
		
		return $count;
	}
	
	function fetchRows($sql, $assoc=true) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		$rows = array();
		$count = mysql_num_rows($result);
		for ($row_no = 0; $row_no < $count; $row_no++) {
			$rows[] = $this->_fetch($result, $assoc);
		}
		
		mysql_free_result($result);
		return $rows;
	}
	
	function fetchOne($sql, $assoc=true) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		if (mysql_num_rows($result)) {
			$row = $this->_fetch($result, $assoc);
		} else {
			$row = null;
		}
		
		mysql_free_result($result);
		return $row;
	}
	
	function _fetch($result, $assoc) {
		$row = array();
		
		foreach (mysql_fetch_row($result) as $index=>$value) {
			$value = $this->_to_native_value($result, $index, $value);
			
			if ($assoc) {
				$index = mysql_field_name($result, $index);
			}
			
			$row[$index] = $value;
		}
		
		return $row;
	}
	
	function _to_native_value($result, $index, $value) {
		if (is_null($value)) return $value;
		
		$type = mysql_field_type($result, $index);
		
		switch ($type) {
			case 'string':
			case 'blob':
			case 'time':
				break;
				
			case 'int':
			case 'year':
				$value = intval($value);
				break;
				
			case 'real':
				$value = floatval($value);
				break;
				
			case 'datetime':
			case 'date':
			case 'timestamp':
				$value = sql_datetime_to_epoch($value);
				break;
		}
		
		return $value;
	}
}

class Kiosk_Data_DB_SQL_MySQL extends Kiosk_Data_DB_SQL {
	function escape($str) {
		return mysql_real_escape_string($str);
	}
	
	function literalBool($value) {
		return $value ? 1 : 0;
	}
}

