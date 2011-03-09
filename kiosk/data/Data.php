<?php

require_once KIOSK_LIB_DIR. '/data/SchemaRepository.php';

define('KIOSK_RESET_BINDINGS',	0x0010);
define('KIOSK_RESET_SOURCES',	0x0020);

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
	
	function bind($class, &$source, $desc_source) {
		$this->_repository->bind($class, $source, $desc_source);
		
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
			$class, 'find', '$query=array()', 
			'return Kiosk_find(CLASS, $query);');
		
		$this->defineFunction(
			$class, 'query', '$params=array()', 
			'return Kiosk_query(CLASS, $params);');
	}
	
	function defineFunction($class, $action, $params, $body) {
		$func = $class. '_'. $action;
		if (function_exists($func)) return;
		
		$code = 'function '. $func. '('. $params. ') {'. str_replace('CLASS', "'{$class}'", $body). '}';
		
		eval($code);
	}
	
	function _findSourceClass($type) {
		$dir = dirname(__FILE__);
		$pattern = '|/'. strtolower($type). '\\.php$|';
		
		foreach (glob("$dir/sources/*.php") as $path) {
			if (preg_match($pattern, strtolower($path))) {
				require_once $path;
				return 'Kiosk_Data_Source_'. $type;
			}
		}
		
		return null;
	}
	
	function &_openSource($config) {
		$type = $config['type'];
		if (! $type) {
			trigger_error(KIOSK_ERROR_CONFIG. 'no source type specified');
			return null;
		}
		
		$class = $this->_findSourceClass($type);
		if (! $class) {
			trigger_error(KIOSK_ERROR_CONFIG. "no source class found with type '{$type}'");
			return null;
		}
		
		$source =& call_user_func(array($class, 'open'), $config);
		return $source;
	}
	
	function &source($name, $config=null) {
		$source = null;
		
		if (is_null($config)) {
			assert('$name');
			
			if (isset($this->_sources[$name])) {
				$source =& $this->_sources[$name];
			}
		} else {
			$source =& $this->_openSource($config);
			if ($source) {
				if ($name) {
					if (isset($this->_sources[$name])) {
						unset($this->_sources[$name]);
					}
					$this->_sources[$name] =& $source;
				}
			}
		}
		
		return $source;
	}
	
	function finalize() {
		$this->_repository->finalize();
	}
	
	function reset($flags) {
		if ($flags & KIOSK_RESET_BINDINGS) {
			$this->_repository->reset();
		}
		
		if ($flags & KIOSK_RESET_SOURCES) {
			$this->_sources = array();
		}
	}
	
	function &schema($class) {
		if (is_object($class)) {
			$class = get_class($class);
		}
		
		return $this->_repository->schema($class);
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
		$object =& $schema->createObject($columns);
		return $object;
	}
	
	function import($class, $args) {
		$items = array();
		$this->_collectImportItems($args, $items);
		
		$schema =& $this->schema($class);
		return $schema->import($items);
	}
	
	function _collectImportItems($args, &$items) {
		if (is_pure_array($args)) {
			foreach ($args as $arg) {
				$this->_collectImportItems($arg, $items);
			}
		} else {
			$items[] = $args;
		}
	}
	
	function load($class, $id, $params) {
		$schema =& $this->schema($class);
		
		return $schema->load($id, $params);
	}
	
	function find(&$class, $query) {
		$schema =& $this->schema($class);
		return $schema->find($query);
	}
	
	function count(&$class, $query) {
		$schema =& $this->schema($class);
		return $schema->count($query);
	}
	
	function query(&$class, $params) {
		$schema =& $this->schema($class);
		return $schema->createQuery($params);
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
