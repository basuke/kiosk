<?php

class Kiosk_SchemaRepository {
	var $_schemas = array();
	
	function bind($class, &$source, $desc_source) {
		$class = strtolower($class);
		
		$schema =& $source->buildSchema($class, $desc_source);
		$this->_schemas[$class] =& $schema;
	}
	
	function finalize() {
		foreach (array_keys($this->_schemas) as $class) {
			if  (! $this->_schemas[$class]->finalized) {
				$this->_schemas[$class]->finalize();
			}
		}
	}
	
	function reset() {
		$this->_schemas = array();
	}
	
	function &schema($class) {
		$class = strtolower($class);
		
		if (empty($this->_schemas[$class])) {
			return trigger_error(KIOSK_ERROR_CONFIG. "class '{$class}' not defined");
		}
		
		$schema =& $this->_schemas[$class];
		if  (! $schema->finalized) {
			$schema->finalize();
		}
		
		return $schema;
	}
	
	function classes() {
		return array_keys($this->_schemas);
	}
}

