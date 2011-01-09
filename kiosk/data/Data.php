<?php

require_once dirname(__FILE__). '/SchemaRepository.php';
require_once dirname(__FILE__). '/Namer.php';

class Kiosk_Data {
	var $_repository;
	var $_sources;
	
	function Kiosk_Data() {
		$this->__construct();
	}
	
	function __construct() {
		$this->_repository =& new Kiosk_SchemaRepository();
		$this->_sources = array();
	}
	
	function bind($class, &$db, $desc_source) {
		$this->_repository->bind($class, $db, $desc_source);
		
		$this->defineFunction(
			$class, 'create', '$columns=array()', 
			'return Kiosk_create(CLASS, $columns);');
		
		$this->defineFunction(
			$class, 'import', '$items', 
			'$args = func_get_args(); return Kiosk_import(CLASS, $args);');
		
		$this->defineFunction(
			$class, 'load', '$id, $params=array()', 
			'return Kiosk_load(CLASS, $id, $params);');
		
		$this->defineFunction(
			$class, 'find', '$params=array()', 
			'return Kiosk_find(CLASS, $params);');
	}
	
	function defineFunction($class, $action, $params, $body) {
		$func = $class. '_'. $action;
		if (function_exists($func)) return;
		
		$code = 'function '. $func. '('. $params. ') {'. str_replace('CLASS', "'{$class}'", $body). '}';
		
		eval($code);
	}
	
	function &source($name, $config=null) {
		$source = null;
		
		if (is_null($config)) {
			if (isset($this->_sources[$name])) {
				$source =& $this->_sources[$name];
			}
		} else {
			if (is_string($config)) {
				$driver = $config;
				$config = array();
			} else {
				$driver = $config['driver'];
				unset($config['driver']);
			}
			
			if (!$driver) {
				return trigger_error(KIOSK_ERROR_CONFIG. 'no source driver specified');
			}
			
			$source =& Kiosk_DB_factory($driver, $config);
			
			if (isset($this->_sources[$name])) {
				unset($this->_sources[$name]);
			}
			$this->_sources[$name] =& $source;
		}
		
		return $source;
	}
	
	function finalize() {
		$this->_repository->finalize();
	}
	
	function reset() {
		$this->_repository->reset();
	}
	
	function &schema($class) {
		if (is_object($class)) {
			$class = get_class($class);
		}
		
		return $this->_repository->schema($class);
	}
	
	function namer() {
		return new Kiosk_Namer();
	}
	
	function apply(&$obj, $values, $override=false) {
		foreach ($values as $key=>$value) {
			if ($override or isset($obj->$key) == false) {
				$obj->$key = $value;
			}
		}
	}
	
	function collect($obj, $keys) {
		$result = array();
		
		foreach ($keys as $key) {
			$result[$key] = (isset($obj->$key) ? $obj->$key : null);
		}
		
		return $result;
	}
	
	function &create($class, $columns) {
		$schema =& $this->schema($class);
		return $schema->createObject($columns);
	}
	
	function import($class, $args) {
		return $this->_import($class, $args, array());
	}
	
	function _import($class, $items, $result) {
		if (is_pure_array($items)) {
			foreach ($items as $columns) {
				$result = $this->_import($class, $columns, $result);
			}
		} else {
			$obj =& $this->create($class, $items);
			if ($obj->save()) {
				$result[] =& $obj;
			} else {
				trigger_error(KIOSK_ERROR_RUNTIME. "failed to save object of class {$class}");
			}
		}
		return $result;
	}
	
	function load($class, $id, $params) {
		$schema =& $this->schema($class);
		
		return $schema->load($id, $params);
	}
	
	function find(&$class, $params) {
		$schema =& $this->schema($class);
		return $schema->find($params);
	}
	
	function save(&$obj) {
		$schema =& $this->schema($obj);
		return $schema->save($obj);
	}
	
	function destroy(&$obj) {
		$schema =& $this->schema($obj);
		return $schema->destroy($obj);
	}
	
	function fetch(&$obj, $name, $params) {
		$schema =& $this->schema($obj);
		return $schema->fetch($obj, $name, $params);
	}
	
	function paramsToFetch(&$obj, $name) {
		$schema =& $this->schema($obj);
		return $schema->paramsToFetch($obj, $name);
	}
}

function &Kiosk_schema($class) {
	$data =& Kiosk_data();
	return $data->schema($class);
}
