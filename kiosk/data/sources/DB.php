<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';

require_once KIOSK_LIB_DIR. '/data/sources/db/Schema.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/Table.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/SQL.php';

class Kiosk_Data_Source_DB extends Kiosk_Data_Source {
	/* static */
	function &open($config) {
		if (is_string($config)) {
			$driver = $config;
			$config = array();
		} else {
			$driver = @$config['driver'];
			unset($config['driver']);
		}
		
		if (!$driver) {
			trigger_error(KIOSK_ERROR_CONFIG. 'no source driver specified');
			$source = null;
			return $source;
		}
		
		$class = Kiosk_Data_Source_DB::_findAndLoadDriverClass($driver);
		if (!$class) {
			trigger_error(KIOSK_ERROR_CONFIG. "no source driver found: {$driver}");
			return null;
		}
		
		$source =& new $class($config);
		return $source;
	}
	
	/* static private */
	function _findAndLoadDriverClass($driver) {
		$dir = dirname(__FILE__);
		$pattern = '|/'. strtolower($driver). '\\.php$|';
		
		foreach (glob("$dir/db/drivers/*.php") as $path) {
			if (preg_match($pattern, strtolower($path))) {
				require_once $path;
				return 'Kiosk_Data_Source_DB_'. $driver;
			}
		}
		
		return null;
	}
	
	var $conn;
	var $config;
	var $language;
	var $tables = array();
	
	function __construct($config) {
		$this->config = $config;
		$this->language = $this->language();
		
		$this->connect();
	}
	
	// schema creation
	
	function &buildSchema($class, $params) {
		extract($params, EXTR_SKIP);
		
		if (empty($name)) {
			$namer = Kiosk::namer();
			$name = $namer->classNameToTableName($class);
		}
		
		$table =& $this->table($name);
		
		$schema_class = 'Kiosk_Schema';
		if (! empty($params['primaryKeys'])) {
			$schema_class = 'Kiosk_Schema_DB_MultiPrimaryKeys';
		} else if ($table->primaryKeyColumn() or $table->column('id') or ! empty($params['primaryKey'])) {
			$schema_class = 'Kiosk_Schema_DB_SinglePrimaryKey';
		} else {
			$schema_class = 'Kiosk_Schema_DB_NoPrimaryKeys';
		}
		
		$schema =& new $schema_class($class, $source, $params);
		
		$schema->name = $name;
		$schema->table =& $table;
		
		return $schema;
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
		
		$start = Kiosk::now();
		
		$result = $this->_query($sql);
		
		$secs = Kiosk::now() - $start;
		
		$this->log(LOG_INFO, sprintf("%.6f %s", $secs, $sql));
		
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
	
	// transaction
	
	function begin() {
		return $this->exec('BEGIN');
	}
	
	function commit() {
		return $this->exec('COMMIT');
	}
	
	function rollback() {
		return $this->exec('ROLLBACK');
	}
}

