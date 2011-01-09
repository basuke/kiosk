<?php

require_once KIOSK_LIB_DIR. '/data/Association.php';

class Kiosk_Association extends Kiosk_Data_Association {
	var $load;
	var $columns;
	
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

