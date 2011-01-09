<?php

require_once KIOSK_LIB_DIR. '/utils/sql.php';
require_once KIOSK_LIB_DIR. '/utils/array.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/Query.php';

class Kiosk_DB_Table {
	var $db;
	var $name;
	var $_describe;
	
	function Kiosk_DB_Table(&$db, $name) {
		$this->db =& $db;
		$this->name = $name;
	}
	
	// =================
	
	function describe() {
		if (empty($this->_describe)) {
			$this->_describe = $this->db->describe($this->name);
		}
		
		return $this->_describe;
	}
	
	function column($name) {
		foreach ($this->describe() as $column) {
			if ($column['name'] == $name) return $column;
		}
		return null;
	}
	
	function primaryKeyColumn() {
		foreach ($this->describe() as $column) {
			if ($column['primaryKey']) return $column;
		}
		return null;
	}
	
	function primaryKeyName() {
		$column = $this->primaryKeyColumn();
		if (empty($column)) return null;
	
		return $column['name'];
	}
	
	function conditionForPrimaryKey($id) {
		return $this->primaryKeyName(). '='. $this->db->literal($id);
	}
	
	function fullColumnName($name) {
		return $this->db->language->fullColumnName($this->name, $name);
	}
	
	function &createQuery() {
		$query =& new Kiosk_Data_Source_DB_Query();
		
		$query->setTable($this->name);
		$query->setDatabase($this->db);
		
		return $query;
	}
	
	function load($id, $params=array()) {
		if (empty($id)) return is_array($id) ? array() : null;
		
		$id_column = $this->primaryKeyName();
		assert('$id_column');
		
		if (is_array($id)) {
			$params['conditions'] = $id_column. ' IN '. $this->db->literal($id);
		} else {
			$params['conditions'] = $this->conditionForPrimaryKey($id);
		}
		
		$query = $this->createQuery();
		$query->setParams($params);
		$rows = $query->fetch();
		
		if (! is_array($id)) {
			return array_first($rows);
		}
		
		$objects = array();
		foreach ($rows as $object) {
			$objects[$object[$id_column]] = $object;
		}
		
		$result = array();
		foreach ($id as $id) {
			$result[$id] = $objects[$id];
		}
		
		return $result;
	}
	
	function select($params=array(), $assoc=true) {
		if (is_string($params)) {
			$params = array('conditions' => $params);
		}
		
		$query = $this->createQuery();
		$query->setParams($params);
		
		if (!$assoc) {
			return $query->fetchRows();
		}
		
		return $query->fetch();
	}
	
	function count($params=array()) {
		if (is_string($params)) {
			$params = array('conditions' => $params);
		}
		
		$query = $this->createQuery();
		$query->setParams($params);
		return $query->count();
	}
	
	function insert($columns) {
		$pk_column = $this->primaryKeyName();
		if ($pk_column) {
			$id = $this->nextId();
			if ($id) {
				$columns[$pk_column] = $id;
			}
		}
		
		$sql = $this->db->language->insertStatement($this->name, $columns);
		return $this->db->exec($sql);
	}
	
	function nextId() {
		return $this->db->nextId($this->name);
	}
	
	function lastId() {
		return $this->db->lastId($this->name);
	}
	
	function update($columns, $conditions) {
		$sql = $this->db->language->updateStatement($this->name, $columns, $conditions);
		return $this->db->exec($sql);
	}
	
	function delete($conditions) {
		$sql = $this->db->language->deleteStatement($this->name, $conditions);
		return $this->db->exec($sql);
	}
}

