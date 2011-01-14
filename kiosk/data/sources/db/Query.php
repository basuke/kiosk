<?php

require_once KIOSK_LIB_DIR. '/data/Query.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/SQL.php';
require_once KIOSK_LIB_DIR. '/utils/array.php';
require_once KIOSK_LIB_DIR. '/utils/sql.php';

class Kiosk_Data_Source_DB_Query extends Kiosk_Data_Query {
	var $db;
	var $language;
	
	var $table = null;
	var $alias = null;
	var $join = array();
	var $group = null;
	var $having = null;
	
	function __construct() {
		$this->language = new Kiosk_Data_DB_SQL();
	}
	
	function setDatabase(&$db) {
		$this->db =& $db;
		$this->language = $db->language;
	}
	
	function setTable($table) {
		$this->table = $table;
	}
	
	function paramsToCollect() {
		return array_merge(
			parent::paramsToCollect(), 
			array(
				'table', 
				'alias', 
				'join', 
				'group', 
				'having', 
			)
		);
	}
	
	// conditions 
	
	function parseConditionOperator($key, $not, $op, $value) {
		if ($op) {
			if ($not) {
				return trigger_error(KIOSK_ERROR_SYNTAX. sprintf("invalid operator '%s' with NOT", $op));
			}
		} else {
			if (is_array($value)) {
				$op = ($not ? 'NOT IN' : 'IN');
			} else if (is_null($value)) {
				$op = ($not ? 'IS NOT' : 'IS');
			} else {
				$op = ($not ? '<>' : '=');
			}
			
			$not = false;
		}
		
		return array($key, $not, $op);
	}
	
	function joinConditions($conditions, $or) {
		return '('. join(($or ? ' OR ' : ' AND '), $conditions). ')';
	}
	
	function notCondition($condition) {
		return 'NOT ('. $condition. ')';
	}
	
	function buildCondition($key, $op, $value) {
		return $key. ' '. $op. ' '. $this->language->literal($value);
	}
	
	/*
		オーダーを解析する
		
		可能な書式
		
			'last_name'
			array('last_name', 'first_name', '-created_date', 'age DESC')
			"last_name, first_name, -created_date, age DESC"
		
	*/
	function parseOrder($order) {
		$orders = array();
		
		$result = parent::parseOrder($order);
		if (!$result) return null;
		
		foreach ($result as $order) {
			list($column, $reverse) = $order;
			$orders[] = $this->language->orderBy($column, $reverse);
		}
		
		return join(',', $orders);
	}
	
	/*
		query execution
	*/
	
	function fetch() {
		$sql = $this->language->selectStatement($this->params());
		return $this->db->fetchRows($sql, true);
	}
	
	function count() {
		$conditions = null;
		extract($this->params());
		
		$params = array(
			'table' => $this->table, 
			'columns'=>'COUNT(*)', 
			'conditions'=>$conditions
		);
		
		$sql = $this->language->selectStatement($params);
		$row = $this->db->fetchOne($sql, false);
		
		return intval($row[0]);
	}
}

class Kiosk_Data_SchemaQuery extends Kiosk_Data_Source_DB_Query {
	var $schema;

	// parsed
	
	var $parsed_columns = null;
	var $parsed_conditions = null;
	var $parsed_having = null;
	var $parsed_order = null;
	
	function setSchema(&$schema) {
		$this->schema =& $schema;
		
		$this->setTable($schema->table->name);
		$this->setDatabase($schema->table->db);
	}
	
	function params() {
		$this->parsed_columns = $this->parseColumns($this->columns);
		$this->columns = null;
		
		$columns = array();
		foreach ($this->parsed_columns as $col) {
			$name = $col->tableColumnName;
			
			$name .= ' AS '. $this->language->quoteName($col->name);
			
			$columns[] = $name;
		}
		
		if ($this->conditions) {
			$this->parsed_conditions = $this->parseConditions($this->conditions);
			$this->conditions = null;
		}
		
		if ($this->having) {
			$this->parsed_having = $this->parseConditions($this->having);
			$this->having = null;
		}
		
		if ($this->order) {
			$this->parsed_order = $this->parseOrder($this->order);
			$this->order = null;
		}
		
		if ($this->first) {
			$this->limit = 1;
		}
		
		$data =& Kiosk_data();
		return $data->collect($this, array(
			'table', 
			'alias', 
			'join', 
			'group', 
			'limit', 
			'offset', 
		)) + array(
			'columns' => $columns, 
			'conditions' => $this->parsed_conditions, 
			'having' => $this->parsed_having, 
			'order' => $this->parsed_order, 
		);
	}
	
	function rowToColumns($row) {
		$columns = array();
		$values = array_values($row);
		
		foreach ($row as $key => $value) {
			if (strpos($key, '.') !== false) {
				$col = $this->parseColumn($key);
				$value = $col->valueForColumn($value);
				$key = $col->columnName();
			} else {
				$key = $this->schema->objectColumnName($key);
			}
			$columns[$key] = $value;
		}
		
		return $columns;
	}
	
	function rowsToObjects($rows) {
		$objects = array();
		
		foreach ($rows as $key=>$row) {
			$columns = $this->rowToColumns($row);
			$objects[$key] = $this->schema->createObject($columns);
		}
		
		foreach ($this->schema->refersTo as $assoc) {
			if (empty($assoc->load)) continue;
			
			$objects = $assoc->loadForObjects($objects);
		}
		
		return $objects;
	}
	
	function addJoin($assoc) {
		$table = $assoc->schema->name;
		$on = $assoc->joinCondition();
		
		$this->join = array('table' => $table, 'on' => $on);
	}
	
	function parseColumns($columns) {
		if (empty($columns) or $columns == '*') {
			$columns = $this->schema->columns;
		}
		
		assert('is_array($columns)');
		
		$columns = array_merge($columns, $this->schema->referenceColumns());
		$result = array();
		
		foreach (array_unique($columns) as $key) {
			$col = $this->parseColumn($key);
			if (! $col) continue;
			
			foreach ($col->associations as $assoc) {
				$this->addJoin($assoc);
			}
			
			$result[] = $col;
		}
		
		return $result;
	}
	
	function parseConditionKey($key, $value) {
		list($key, $not, $op) = parent::parseConditionKey($key, $value);
		
		$key = $this->schema->fullTableColumnName($key);
		
		return array($key, $not, $op);
	}
	
	function parseConditionValue($value) {
		if (is_object($value)) {
			$data =& Kiosk_data();
			$another_schema = $data->schema($value);
			
			if ($another_schema) {
				$value = $another_schema->getId($value);
			}
		}
		
		return parent::parseConditionValue($value);
	}
	
	function parseOrderColumn($column) {
		$result = parent::parseOrderColumn($column);
		if (! $result) return false;
		
		list($column, $reverse) = $result;
		
		$col = $this->parseColumn($column);
		if (! $col) return false;
		
		foreach ($col->associations as $assoc) {
			$this->addJoin($assoc);
		}
		
		return array($col->tableColumnName, $reverse);
	}
	
	function parseColumn($name) {
		$col = new Kiosk_Data_SchemaQueryColumn($this->schema, $name);
		
		if (empty($col->tableColumnName)) {
			trigger_error(KIOSK_ERROR_CONFIG. "column '{$key}' doesn't exists");
			return null;
		}
			
		return $col;
	}
}

class Kiosk_Data_SchemaQueryColumn {
	var $path;
	var $associations = array();
	var $tableColumnName;
	
	function Kiosk_Data_SchemaQueryColumn(&$schema, $name) {
		$this->__construct($schema, $name);
	}
	
	function __construct(&$schema, $name) {
		$this->schema =& $schema;
		$this->name = $name;
		$this->path = explode('.', $name);
		
		$this->tableColumnName =  $this->schema->resolveColumnNamePath($this->path, $this->associations);
	}
	
	function columnName() {
		return $this->path[0];
	}
	
	function valueForColumn($value) {
		return array_to_object(array($this->path[1] => $value));
	}
}

