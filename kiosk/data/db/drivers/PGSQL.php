<?php

require_once dirname(dirname(__FILE__)). '/Driver.php';
require_once 'Kiosk.php';

class Kiosk_DB_Driver_PGSQL extends Kiosk_DB_Driver {
	var $_types = array(
		'integer' => 'integer', 
		'smallint' => 'integer', 
		
		'"char"' => 'string',
		'character varying' => 'string',
		'bigint' => 'string',
		'bytea' => 'string',
		'name' => 'string',
		'oid' => 'string',
		'inet' => 'string',
		'text' => 'string',
		'xid' => 'string',
		
		'boolean' => 'bool', 
		
		'double precision' => 'float', 
		'interval' => 'float', 
		'real' => 'float', 
		
		'timestamp without time zone' => 'timestamp', 
		'timestamp with time zone' => 'timestamp', 
		'date' => 'timestamp', 
	);
	
	var $_lastId = null;
	var $_schema = 'public';
	
	function language() {
		return new Kiosk_Data_DB_SQL_PGSQL();
	}
	
	function connect() {
		$this->conn = pg_connect($this->_buildConnectionString($this->config));
	}
	
	function disconnect() {
		if ($this->conn) {
			pg_close($this->conn);
		}
	}
	
	function tables() {
		$sql = "SELECT table_name as name FROM INFORMATION_SCHEMA.tables WHERE table_schema = '{$this->_schema}' ORDER BY 1";
		
		$rows = $this->fetchRows($sql);
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
		$table = strtolower($table);
		
		$columns = array();
		
		$sql = "SELECT DISTINCT column_name AS name, data_type AS type, is_nullable AS null, column_default AS default FROM information_schema.columns WHERE table_name = '{$table}' AND table_schema = '{$this->_schema}'";
		
		foreach ($this->fetchRows($sql) as $info) {
			extract($info);
			
			$sequence = null;
			$primary_key = false;
			
			if (isset($this->_types[$type])) {
				$type = $this->_types[$type];
			}
			
			if (preg_match('/nextval\(+[\'"]?([\w.]+)/', $default, $match)) {
				$sequence = $match[1];
				$default = Kiosk::func(array($this, 'nextId'), $match[1]);
				$primary_key = true;
			}
			
			$columns[$name]['name'] = $name;
			$columns[$name]['type'] = $type;
			$columns[$name]['notNull'] = ($null == 'NO');
			$columns[$name]['primaryKey'] = $primary_key;
			$columns[$name]['sequence'] = $sequence;
		}
		
		return $columns;
	}

	function _query($sql) {
		return pg_query($this->conn, $sql);
	}
	
	function exec($sql) {
		$result = $this->query($sql);
		if (is_null($result)) return -1;
		
		$count = pg_affected_rows($result);
		pg_free_result($result);
		return $count;
	}
	
	function nextId($table) {
		$table = $this->table($table);
		$column = $table->primaryKeyColumn();
		$sequence = $column['sequence'];
		if (!$sequence) return null;
		
		list($value, ) = $this->fetchOne("SELECT nextval('{$sequence}')", false);
		
		$this->_lastId = $value;;
		return $value;
	}
	
	function lastId($table) {
		return $this->_lastId;
	}
	
	function lasterror() {
		return pg_last_error($this->conn);
	}
	
	function count($sql) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		$count = pg_num_rows($result);
		pg_free_result($result);
		
		return $count;
	}
	
	function fetchRows($sql, $assoc=true) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		$rows = array();
		$count = pg_num_rows($result);
		for ($row_no = 0; $row_no < $count; $row_no++) {
			$rows[] = $this->_fetch($result, $row_no, $assoc);
		}
		
		pg_free_result($result);
		return $rows;
	}
	
	function fetchOne($sql, $assoc=true) {
		$result = $this->query($sql);
		if (!$result) return null;
		
		if (pg_num_rows($result)) {
			$row = $this->_fetch($result, 0, $assoc);
		} else {
			$row = null;
		}
		
		pg_free_result($result);
		return $row;
	}
	
	function textSearchCondition($column, $value) {
		return $column. ' ILIKE '. $this->quote('%'. $value. '%');
	}
	
	function _fetch($result, $row_no, $assoc) {
		$row = array();
		
		foreach (pg_fetch_row($result, $row_no) as $index=>$value) {
			$value = $this->_to_native_value($result, $row_no, $index, $value);
			
			if ($assoc) {
				$index = pg_field_name($result, $index);
			}
			
			$row[$index] = $value;
		}
		
		return $row;
	}
	
	function _to_native_value($result, $row_no, $index, $value) {
		if (pg_field_is_null($result, $row_no, $index)) return null;
		
		$type = pg_field_type($result, $index);
		
		switch ($type) {
			case 'text':
				break;
				
			case 'bool':
				$value = ($value == 't');
				break;
				
			case 'float8':
				$value = floatval($value);
				break;
				
			case 'int':
			case 'int4':
				$value = intval($value);
				break;
				
			case 'int8':
				$value = $value;
				break;
				
			case 'timestamptz':
			case 'timestamp':
				$value = sql_datetime_to_epoch($value);
				break;
		}
		
		return $value;
	}
	
	function _buildConnectionString($config) {
		$str = 'dbname='. $config['db'];
		
		if (!empty($config['host'])) $str .= ' host='. $config['host'];
		if (!empty($config['port'])) $str .= ' port='. $config['port'];
		if (!empty($config['db'])) $str .= ' dbname='. $config['db'];
		if (!empty($config['user'])) $str .= ' user='. $config['user'];
		if (!empty($config['password'])) $str .= ' password='. $config['password'];
		
		return $str;
	}
}

class Kiosk_Data_DB_SQL_PGSQL extends Kiosk_Data_DB_SQL {
	function escape($str) {
		return pg_escape_string($str);
	}
}

