<?php

/**
	Association class is to handle variaous association operation between two schemas.
*/

class Kiosk_Data_Association {
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
}

