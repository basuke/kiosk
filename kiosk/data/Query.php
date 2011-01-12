<?php

class Kiosk_Data_Query {
	var $columns = '*';
	var $conditions = null;
	var $order = null;
	var $limit = 0;
	var $offset = 0;
	
	function Kiosk_Data_Query() {
		$this->__construct();
	}
	
	function __construct() {
	}
	
	function setParams($params) {
		$data =& Kiosk_data();
		$data->apply($this, $params, true);
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
		$data =& Kiosk_data();
		return $data->collect($this, $this->paramsToCollect());
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

