<?php

class Kiosk_Data_Query {
	var $columns = '*';
	var $conditions = null;
	var $order = null;
	var $limit = 0;
	var $offset = 0;
	
	var $first = false;
	var $raw = false;
	
	function Kiosk_Data_Query() {
		$this->__construct();
	}
	
	function __construct() {
	}
	
	function setParams($params) {
		foreach ($params as $key => $value) {
			if (is_integer($key)) {
				$this->$value = true;
			} else {
				$this->$key = $value;
			}
		}
	}
	
	function paramsToCollect() {
		return array(
			'columns', 
			'conditions', 
			'order', 
			'limit', 
			'offset', 
		);
	}
	
	function params() {
		if ($this->first) {
			$this->limit = 1;
		}
		
		$data =& Kiosk_data();
		return $data->collect($this, $this->paramsToCollect());
	}
	
	// fetch ==================================
	
	function fetch() {
		return array();
	}
	
	function count() {
		return 0;
	}
	
	// conditions =============================
	
	function parseConditions($conditions, $or=false) {
		if (is_string($conditions)) {
			$conditions = array($conditions);
		}
		
		if (is_array($conditions) == false) {
			return trigger_error(KIOSK_ERROR_SYNTAX. 
				sprintf("invalid condition '%s'", $conditions));
		}
		
		$components = array();
		foreach ($conditions as $key => $value) {
			if (is_integer($key)) {
				if (is_array($value)) {
					$value = $this->parseConditions($value);
				}
				$components[] = $value;
			} else if ($key === 'AND' || $key === 'OR') {
				$value = $this->parseConditions($value, $key == 'OR');
				
				$components[] = $value;
			} else if ($key === 'NOT') {
				$value = $this->parseConditions($value);
				
				$components[] = $this->notCondition($value);
			} else {
				assert('is_string($key)');
				
				list($key, $not, $op, $value) = $this->parseCondition(trim($key), $value);
				
				$cond = $this->buildCondition($key, $op, $value);
				
				if ($not) {
					$cond = $this->notCondition($cond);
				}
				
				$components[] = $cond;
			}
		}
		
		$components = array_filter($components);
		
		if (empty($components)) {
			return null;
		}
		
		if (count($components) == 1) {
			return $components[0];
		}
		
		return $this->joinConditions($components, $or);
	}
	
	function parseCondition($key, $value) {
		assert('is_string($key)');
		
		$value = $this->parseConditionValue($value);
		
		list($key, $not, $op) = $this->parseConditionKey(trim($key), $value);
		
		return array($key, $not, $op, $value);
	}
	
	function parseConditionKey($key, $value) {
		$not = false;
		$op = null;
		
		// NOT(!)の処理
		while (preg_match('/^([!]|NOT ) *(.+)/', $key, $match)) {
			$key = $match[2];
			$not = !$not;
		}
		
		list($key, $op) = qw($key, 2);
		
		return $this->parseConditionOperator($key, $not, $op, $value);
	}
	
	function parseConditionOperator($key, $not, $op, $value) {
		if (!$op) {
			if (is_array($value)) {
				$op = 'IN';
			} else if (is_null($value)) {
				$op = 'IS';
			} else {
				$op = '=';
			}
		}
		
		return array($key, $not, $op);
	}
	
	function parseConditionValue($value) {
		return $value;
	}
	
	function joinConditions($conditions, $or) {
		assert('is_array($conditions)');
		return array(($or ? '|' : '&') => $conditions);
	}
	
	function notCondition($condition) {
		return array('!' => $condition);
	}
	
	function buildCondition($key, $op, $value) {
		return array($op => array($key, $value));
	}
	
	// order ==================================
	
	/*
		オーダーを解析する
		
		可能な書式
		
			'last_name'
			array('last_name', 'first_name', '-created_date', 'age DESC')
			"last_name, first_name, -created_date, age DESC"
		
	*/
	function parseOrder($order) {
		$orders = array();
		
		foreach (cs($order) as $token) {
			$token = trim($token);
			if (empty($token)) continue;
			
			$result = $this->parseOrderColumn($token);
			if (! $result) {
				trigger_error(KIOSK_ERROR_SYNTAX. "invalid order '{$token}'");
				return null;
			}
			
			$orders[] = $result;
		}
		
		return $orders;
	}
	
	function parseOrderColumn($column) {
		$reverse = false;
		
		if ($column[0] == '-') {
			$reverse = true;
			$column = substr($column, 1);
		}
		
		if (strpos($column, ' ') !== false) {
			list($column, $order) = qw($column, 2);
			switch (strtoupper($order)) {
				case 'ASC':
					break;
					
				case 'DESC':
					$reverse = !$reverse;
					break;
					
				default:
					return false;
			}
		}
		
		if (preg_match('/^[a-z_]+(\\.[a-z._]+)?$/i', $column) == false) {
			return false;
		}
		
		return array($column, $reverse);
	}
	
}

