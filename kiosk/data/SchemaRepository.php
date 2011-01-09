<?php

require_once KIOSK_LIB_DIR. '/data/Schema.php';

class Kiosk_SchemaRepository {
	var $_schemas = array();
	
	function bind($class, &$db, $desc_source) {
		$class = strtolower($class);
		
		$schema =& $this->buildSchema($class, $db, $desc_source);
		$this->_schemas[$class] =& $schema;
	}
	
	function finalize() {
		foreach (array_keys($this->_schemas) as $class) {
			$this->finalizeSchema($this->_schemas[$class]);
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
		if  (!$schema->finalized) {
			$this->finalizeSchema($schema);
		}
		
		return $schema;
	}
	
	function classes() {
		return array_keys($this->_schemas);
	}
	
	function namer() {
		$data =& Kiosk_data();
		return $data->namer();
	}
	
	function &buildSchema($class, &$db, $params) {
		extract($params, EXTR_SKIP);
		
		if (empty($name)) {
			$namer = $this->namer();
			$name = $namer->classNameToTableName($class);
		}
		
		$table =& $db->table($name);
		
		$schema_class = 'Kiosk_Schema';
		if (! empty($params['primaryKeys'])) {
			$schema_class = 'Kiosk_Schema_DB_MultiPrimaryKeys';
		} else if ($table->primaryKeyColumn() or $table->column('id')) {
			$schema_class = 'Kiosk_Schema_DB_SinglePrimaryKey';
		} else {
			$schema_class = 'Kiosk_Schema_DB_NoPrimaryKeys';
		}
		
		$schema =& new $schema_class();
		foreach ($params as $key=>$value) {
			$schema->$key = $value;
		}
		
		$schema->class = $class;
		$schema->name = $name;
		$schema->table =& $table;
		$schema->finalized = false;
		
		return $schema;
	}
	
	function finalizeSchema(&$schema) {
		if ($schema->finalized) return;
		
		$schema->finalized = true;
		
		if (empty($schema->columns)) {
			$schema->columns = array_keys($schema->table->describe());
		}
		
		if (empty($schema->defaults)) {
			$schema->defaults = array();
		}
		
		$this->buildColumnsMap($schema);
		
		$this->buildCallbacks($schema);
		
		$this->parseAssociation($schema, 'refersTo');
		$this->parseAssociation($schema, 'belongsTo');
		$this->parseAssociation($schema, 'hasOne');
		$this->parseAssociation($schema, 'hasMany');
		
		$schema->refersTo += $schema->belongsTo;
		unset($schema->belongsTo);
	}
	
	function buildCallbacks(&$schema) {
		foreach (array('beforeSave', 'afterFetch', 'afterLoad') as $callback) {
			if (! empty($schema->$callback)) {
				if (!is_a($schema->$callback, 'Kiosk_Callable')) {
					return trigger_error(KIOSK_ERROR_CONFIG. "cannot call {$schema->$callback}");
				}
			} else {
				$schema->$callback = null;
			}
		}
	}
	
	function buildColumnsMap(&$schema) {
		$schema->db_columns = array();
		$schema->obj_columns = array();
		
		foreach ($schema->columns as $obj_name => $db_name) {
			if (is_integer($obj_name)) {
				$obj_name = $db_name;
			}
			
			$schema->db_columns[$obj_name] = $db_name;
			$schema->obj_columns[$db_name] = $obj_name;
		}
	}
	
	
	/*
		関連の定義情報を解析して、利用しやすい形にまとめる
	*/
	function parseAssociation(&$schema, $type) {
		if (empty($schema->$type)) {
			$schema->$type = array();
			return;
		}
		
		$items = array();
		
		foreach ((array) $schema->$type as $class=>$info) {
			if (is_integer($class)) {
				if (is_string($info)) {
					/*
						'Image', ...
					*/
					
					$info = array('class' => $info);
				} else {
					/*
						array('class' => 'Image', ...), ...
					*/
					
					assert('is_array($info)');
				}
			} else {
				/*
					'Image' => array( ...), ...
				*/
				assert('is_array($info)');
				
				if (! isset($info['class'])) {
					$info['class'] = $class;
				} else if (! isset($info['name'])) {
					$info['name'] = $class;
				}
			}
			
			$assoc =& Kiosk_Association::create($type, $schema->class, $info);
			
			if (is_null($assoc)) {
				trigger_error(KIOSK_ERROR_CONFIG. "{$type} association definition for class {$schema->class} is invalid.");
				continue;
			}
			
			$items[$assoc->name] =& $assoc;
		}
		
		$schema->$type = $items;
	}
}

