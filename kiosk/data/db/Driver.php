<?php

require_once dirname(__FILE__). '/Table.php';
require_once dirname(__FILE__). '/SQL.php';

class Kiosk_DB_Driver {
	var $conn;
	var $config;
	var $language;
	var $tables = array();
	var $logger;
	
	function Kiosk_DB_Driver($config=array()) {
		$this->__construct($config);
	}
	
	function __construct($config=array()) {
		$this->config = $config;
		$this->language = $this->language();
		
		$this->connect();
	}
	
	function setLogger(&$logger) {
		$this->logger =& $logger;
	}
	
	/* database operation */
	
	function connect() {
		return null;
	}
	
	function disconnect() {
	}
	
	function tables() {
		return array();
	}
	
	function describe($table) {
		return null;
	}
	
	function query($sql) {
		$sql = trim($sql);
		
		if ($this->logger) {
			$start = Kiosk::now();
		}
		
		$result = $this->_query($sql);
		
		if ($this->logger) {
			$secs = Kiosk::now() - $start;
			
			$this->logger->log(LOG_INFO, sprintf("%.6f %s", $secs, $sql));
		}
		
		if (!$result) {
			return null;
		}
		
		return $result;
	}
	
	function _query($sql) {
		return null;
	}
	
	function exec($sql) {
		$this->query($sql);
		return false;
	}
	
	function nextId($table) {
		return null;
	}
	
	function lastId($table) {
		return null;
	}
	
	function count($sql) {
		return null;
	}
	
	function fetchRows($sql, $assoc=true) {
		return array();
	}
	
	function fetchOne($sql, $assoc=true) {
		return null;
	}
	
	function dump($tables) {
		$result = array();
		
		foreach ((array) $tables as $name) {
			$table = $this->table($name);
			$result[$name] = $table->select();
		}
		
		return $result;
	}
	
	function language() {
		return new Kiosk_Data_DB_SQL();
	}
	
	/* language utility */
	
	function escape($str) {
		return $this->language->escape($str);
	}
	
	function quote($str) {
		return $this->language->quote($str);
	}
	
	function quoteName($word) {
		return $this->language->quoteName($word);
	}
	
	function literal($value) {
		return $this->language->literal($value);
	}
	
	/* table object */
	
	function &table($name) {
		if (isset($this->tables[$name]) == false) {
			$this->tables[$name] =& new Kiosk_DB_Table($this, $name);
		}
		
		return $this->tables[$name];
	}
}

