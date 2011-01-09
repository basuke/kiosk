<?php

/**
	Association class is to handle variaous association operation between two tables.
*/

class Kiosk_Association {
	/**
		Create new Association object with parametes. Association is the 
		relation of two classes. Usually there is origin class and target
		class.
		
		For 'refersTo' and 'belongsTo', the instance of origin class
		has a column to refer to the target class.
		
		For 'hasMany' and 'hasOne', the instances of target class have
		a column to be distinguished.
		
		Parameter
		==========
		- class
				class name of the target.
		- name (optional)
				name of the association.
		- column (optional)
				The column name to be used to connect with.
				For belongsTo and refersTo association, it is the 
				name of origin class column. For other association, 
				it is the name of target class column.
		
		@params $type string
				type of association to be created.
				one of 'belongsTo', 'referesTo', 'hasMany' or 'hasOne'
		@params $origin_class string
				class name of association origin
		@params $target_info array
				assistant information for the association.
		@return association object
		
		@access static
	*/
	/* static public */
	function &create(
		$type, 
		$origin_class, 
		$target_info)
	{
		assert('is_string($origin_class)');
		assert('is_string($type)');
		assert('is_array($target_info)');
		
		$assoc_class = 'Kiosk_'. ucfirst($type). 'Association';
		assert('class_exists($assoc_class)');
		
		$assoc =& new $assoc_class($origin_class, $target_info);
		if (empty($assoc->name)) return null;
		
		return $assoc;
	}
	
	var $center;		// copy of global Kiosk_Data object
	var $origin_schema;
	var $schema;
	
	var $class;
	var $name;
	var $column;
	
	var $load;
	var $columns;
	
	function Kiosk_Association($origin_class, $target_info) {
		$this->__construct($origin_class, $target_info);
	}
	
	function __construct($origin_class, $target_info) {
		$this->center =& Kiosk_data();
		$this->center->apply($this, $target_info);
		
		$this->origin_schema =& $this->center->schema($origin_class);
		$this->schema =& $this->center->schema($this->class);
	}
	
	function fetch(&$obj, $params) {
		assert('false; ERROR');
	}
	
	function paramsToFetch(&$obj) {
		return array();
	}
	
	function loadForObjects($objects) {
		$name = $this->name;
		$column = $this->column;
		
		$ids = array_filter(array_unique(array_collect_value($objects, $column)));
		
		$params = array(
			'columns' => $this->columns, 
		);
		
		$refs = $this->center->load($this->class, $ids, $params);
		
		foreach ($objects as $key=>$object) {
			if (isset($object->$column)) {
				$id = $object->$column;
				$object->$name = (isset($refs[$id]) ? $refs[$id] : null);
				unset($object->$column);
			}
			
			$objects[$key] = $object;
		}
		
		return $objects;
	}
	
	function beforeSave(&$obj) {
	}
	
	function hasColumnInOrigin() {
		return false;
	}
}

class Kiosk_RefersToAssociation extends Kiosk_Association {
	function __construct($origin_class, $target_info) {
		parent::__construct($origin_class, $target_info);
		
		$namer = Kiosk::namer();
		
		if (empty($this->name)) {
			$name = $namer->classNameToTableName($this->class);
			$this->name = $name;
		}
		
		if (empty($this->column)) {
			$this->column = $namer->tableNameToColumnName($this->schema->name);
		}
	}
	
	function fetch(&$obj, $params) {
		$column = $this->column;
		$name = $this->name;
		
		$id = $obj->$column;
		
		$params += array(
			'columns' => $this->columns, 
		);
		
		$obj->$name =& $this->center->load($this->class, $id, $params);
		
		return $obj->$name;
	}
	
	function beforeSave(&$obj) {
		$name = $this->name;
		if (! isset($obj->$name)) return;
		
		if ($this->schema->isSaved($obj->$name) == false) {
			$this->center->save($obj->$name);
		}
		
		$column = $this->column;
		$obj->$column = $this->schema->getId($obj->$name);
	}
	
	function hasColumnInOrigin() {
		return true;
	}
}

class Kiosk_BelongsToAssociation extends Kiosk_RefersToAssociation {
}

class Kiosk_HasManyAssociation extends Kiosk_Association {
	var $order;
	var $conditions = array();
	
	function __construct($origin_class, $target_info) {
		parent::__construct($origin_class, $target_info);
		
		$namer = Kiosk::namer();
		
		if (empty($this->name)) {
			$name = $namer->classNameToTableName($this->class);
			$this->name = $namer->pluralize($name);
		}
		
		if (empty($this->column)) {
			$name = $this->origin_schema->name;
			$this->column = $namer->tableNameToColumnName($name);
		}
		
	}
	
	function fetch(&$obj, $params) {
		$more = $this->paramsToFetch($obj);
		if (is_null($more)) return null;
		
		$params += $more;
		
		return $this->center->find($this->class, $params);
	}
	
	function paramsToFetch(&$obj) {
		$id = $this->origin_schema->getId($obj);
		if (!$id) return null;
		
		$conditions = $this->conditions;
		$conditions[$this->column] = $id;
		
		return array(
			'conditions' => $conditions, 
			'columns' => $this->columns, 
			'order' => $this->order, 
		);
	}
}

class Kiosk_HasOneAssociation extends Kiosk_HasManyAssociation {
	function fetch(&$obj, $params) {
		$result = parent::fetch($obj, $params);
		
		$name = $this->name;
		$obj->$name = $result;
		return $obj->$name;
	}
	
	function paramsToFetch(&$obj) {
		$params = parent::paramsToFetch($obj);
		$params['first'] = true;
		return $params;
	}
	
	function joinCondition() {
		$pkey = $this->origin_schema->table->primaryKeyName();
		$key = $this->origin_schema->fullTableColumnName($pkey);
		
		$value = $this->schema->fullTableColumnName($this->column);
		
		return array($key => $value);
	}
}

