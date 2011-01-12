<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/Schema.php';

class Kiosk_Data_Source_DB extends Kiosk_Data_Source {
	/* static */
	function &open($config) {
		if (is_string($config)) {
			$driver = $config;
			$config = array();
		} else {
			$driver = $config['driver'];
			unset($config['driver']);
		}
		
		if (!$driver) {
			trigger_error(KIOSK_ERROR_CONFIG. 'no source driver specified');
			return null;
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
				return 'Kiosk_DB_Driver_'. $driver;
			}
		}
		
		return null;
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
}

