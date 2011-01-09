<?php

/*
	SQL language generation.
*/

class Kiosk_Data_DB_SQL {
	/* literals */
	
	function escape($str) {
		return sql_escape($str);
	}
	
	function quote($str) {
		return "'". $this->escape($str). "'";
	}
	
	function quoteName($word) {
		return '"'. $this->escape($word). '"';
	}
	
	function literal($value) {
		if (is_null($value)) {
			return $this->literalNull();
		}
		
		if (is_array($value)) {
			return $this->literalArray($value);
		}
		
		if (is_bool($value)) {
			return $this->literalBool((bool) $value);
		}
		
		if (is_numeric($value)) {
			return $this->literalNumeric($value);
		}
		
		if (ctype_digit($value)) {
			return $this->literalNumeric(intval($value));
		}
		
		return $this->literalString($value);
	}
	
	function literalNull() {
		return 'NULL';
	}
	
	function literalArray($values) {
		return '('. join(',' , array_map(array($this, 'literal'), $values)). ')';
	}
	
	function literalBool($value) {
		return $value ? 'TRUE' : 'FALSE';
	}
	
	function literalNumeric($value) {
		return $value;
	}
	
	function literalString($value) {
		return $this->quote($value);
	}
	
	/* statements */
	
	function selectStatement($params) {
		$table = '';
		$join = null;
		$columns = '*';
		$conditions = '';
		$group = '';
		$having = '';
		$order = '';
		$limit = 0;
		$offset = 0;
		
		extract($params);
		
		$table = $this->quoteName($table);
		$columns = $this->buildSelectColumns($columns);
		
		$sql = 'SELECT '. $columns;
		if ($table) {
			if ($join) {
				if (! is_pure_array($join)) {
					$join = array($join);
				}
				
				foreach ($join as $join) {
					$table = $this->buildSelectTable($table, $join);
				}
			}
			$sql .= ' FROM '. $table;
		}
		if ($conditions) $sql .= ' WHERE '. $conditions;
		if ($group) $sql .= ' GROUP BY '. $group;
		if ($having) $sql .= ' HAVING '. $having;
		if ($order) $sql .= ' ORDER BY '. $order;
		if ($limit) $sql .= ' LIMIT '. $limit;
		if ($offset) $sql .= ' OFFSET '. $offset;
		
		return $sql;
	}
	
	function buildSelectColumns($columns) {
		if (is_string($columns)) return $columns;
		
		assert('is_array($columns)');
		return join(',', $columns);
	}
	
	function buildSelectTable($base, $join) {
		$table = null;
		$alias = '';
		$on = null;
		
		extract($join);
		
		if (empty($table) or empty($on)) {
			trigger_error(KIOSK_ERROR_CONFIG. "no table specified in join");
			return $base;
		}
		
		if ($alias) {
			$table .= ' AS '. $alias;
		}
		
		$base .= ' INNER JOIN '. $table. ' ON (';
		if (is_string($on)) {
			$base .= $on;
		} else {
			foreach ($on as $key=>$value) {
				$base .= $key. '='. $value;
				break;
			}
		}
		$base .= ')';
		return $base;
	}
	
	function textSearchConditions($target_columns, $words, $and = true) {
		$conditions = array();
		$op = ($and ? ' AND ' : ' OR ');
		
		foreach ((array) $target_columns as $column) {
			$sub = array();
			foreach ($words as $word) {
				$sub[] = $this->textSearchCondition($column, $word);
			}
			$conditions[] = '('. join($op, $sub). ')';
		}
		
		return join(' OR ', $conditions);
	}
	
	function textSearchCondition($column, $value) {
		return $column. ' LIKE '. $this->quote('%'. $value. '%');
	}
	
	function insertStatement($table, $columns) {
		$values = array();
		foreach (array_values($columns) as $value) {
			$values[] = $this->literal($value);
		}
		
		$sql = 'INSERT INTO '. $this->quoteName($table);
		$sql .= '('. join(',', array_keys($columns)). ')';
		$sql .= ' VALUES ('. join(',', $values). ')';
		
		return $sql;
	}
	
	function updateStatement($table, $columns, $conditions) {
		$exprs = array();
		foreach ((array) $columns as $key => $value) {
			if (is_integer($key)) {
				$exprs[] = $value;
			} else {
				$exprs[] = $key. '='. $this->literal($value);
			}
		}
		
		$sql = 'UPDATE '. $this->quoteName($table);
		$sql .= ' SET '. join(',', $exprs);
		$sql .= ' WHERE '. $conditions;
		
		return $sql;
	}
	
	function deleteStatement($table, $conditions) {
		$sql = 'DELETE FROM '. $this->quoteName($table);
		$sql .= ' WHERE '. $conditions;
		
		return $sql;
	}
	
	function fullColumnName($table, $name) {
		return $this->quoteName($table). '.'. $this->quoteName($name);
	}
	
	function orderBy($column, $reverse) {
		if ($reverse) {
			$column .= ' DESC';
		}
		
		return $column;
	}
}

