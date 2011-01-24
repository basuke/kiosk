<?php

require_once KIOSK_LIB_DIR. '/data/Schema.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/Query.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/Association.php';

define('Kiosk_INCLUDE_PRIMARY_KEYS', false);
define('Kiosk_WITHOUT_PRIMARY_KEYS', true);

class Kiosk_Schema extends Kiosk_Data_Schema {
	var $name;		// テーブル名
	var $table;		// テーブルオブジェクト
	
	var $primaryKey;	// プライマリーキーのカラム名
	
	var $columns;	// カラム定義。実際にはdb_columnsとobj_columnsにマップを作る
	
	var $db_columns;	// キーがオブジェクトのカラム名で値がDBのカラム名のマッピング
	var $obj_columns;	// キーがDBのカラム名で値がオブジェクトのカラム名のマッピング
	
	function queryClass() {
		return 'Kiosk_Data_SchemaQuery';
	}
	
	function primaryKeyName() {
		if ($this->primaryKey) {
			return $this->primaryKey;
		}
		
		$name = $this->table->primaryKeyName();
		if ($name) {
			return $name;
		}
		
		return null;
	}
	
	// id
	
	function isSaved($obj) {
		$pk = $this->primaryKeyName();
		if (! $pk) return false;
		
		return !empty($obj->$pk);
	}
	
	function getId($obj) {
		$pk = $this->primaryKeyName();
		if (! $pk) return null;
		
		if (empty($obj->$pk)) return null;
		
		return $obj->$pk;
	}
	
	function setId(&$obj, $id) {
		$pk = $this->primaryKeyName();
		if (! $pk) {
			trigger_error(KIOSK_ERROR_CONFIG. "No primary key in schema {$this->name}");
			return;
		}
		
		$obj->$pk = $id;
	}
	
	// column value
	
	function defaultValues() {
		$values = array();
		
		foreach ($this->defaults as $key => $value) {
			if (is_a($value, 'Kiosk_Callable')) {
				$value = $value->call();
			}
			
			$values[$key] = $value;
		}
		
		return $values;
	}
	
	function collectValues($obj, $without_primary_keys) {
		$values = array();
		
		foreach ((array) $obj as $key => $value) {
			$column = $this->nativeColumnName($key);
			if (! $column) continue;
			
			if (is_object($value)) {
				$data =& Kiosk_data();
				$another_desc = $data->schema($value);
				
				if ($another_desc) {
					$value = $another_desc->getId($value);
				}
			}
			
			$values[$column] = $value;
		}
		
		if ($without_primary_keys) {
			unset($values[$this->table->primaryKeyName()]);
		}
		
		return $values;
	}
	
	// column name conversion and get information
	
	function nativeColumnName($name) {
		if (isset($this->db_columns[$name])) {
			return $this->db_columns[$name];
		}
		
		$assoc = $this->associationWithName($name);
		if ($assoc and $assoc->hasColumnInOrigin()) {
			return $assoc->column;
		}
		
		if (in_array($name, array_keys($this->table->describe()))) {
			return $name;
		}
		
		return null;
	}
	
	function fullNativeColumnName($name) {
		$name = $this->nativeColumnName($name);
		if (!$name) return null;
		
		return $this->table->fullColumnName($name);
	}
	
	function resolveColumnNamePath($path, &$associations) {
		assert('is_array($path)');
		assert('count($path) >= 1');
		assert('is_array($associations)');
		
		$name = array_shift($path);
		if (empty($path)) {
			return $this->fullNativeColumnName($name);
		}
		
		$assoc =& $this->associationWithName($name);
		if (!$assoc) return false;
		
		$associations[$name] = $assoc;
		
		return $assoc->schema->resolveColumnNamePath($path, $associations);
	}
	
	function objectColumnName($name) {
		if (empty($this->obj_columns[$name])) return $name;
		
		return $this->obj_columns[$name];
	}
	
	function referenceColumns() {
		$columns = array();
		
		foreach ($this->refersTo as $assoc) {
			$columns[] = $assoc->column;
		}
		
		return $columns;
	}
	
	// filter
	
	function applyFilter($objects, $filter) {
		assert('is_array($objects)');
		
		$result = array();
		
		foreach ($objects as $index=>$object) {
			$result[$index] = $filter->call($object);
		}
		
		return $result;
	}
	
	// query parse and build
	
	function conditionForPrimaryKey($id) {
		assert('false /* no primary key information */');
		return array('0=1');
	}
	
	// association
	
	function &associationWithName($name) {
		foreach ($this->refersTo as $key=>$assoc) {
			if ($assoc->name == $name) return $this->refersTo[$key];
		}
		
		foreach ($this->hasMany as $key=>$assoc) {
			if ($assoc->name == $name) return $this->hasMany[$key];
		}
		
		foreach ($this->hasOne as $key=>$assoc) {
			if ($assoc->name == $name) return $this->hasOne[$key];
		}
		
		$assoc = null;
		return $assoc;
	}
	
	// data manimuration
	
	function load($id, $params) {
		return trigger_error(KIOSK_ERROR_CONFIG. "cannot load {$class} / no primary key");
	}
	
	function findWithQuery(&$query) {
		return $query->fetch();
	}
	
	function rowsToObjects($rows, &$query) {
		$objects = array();
		
		foreach ($rows as $key=>$row) {
			$columns = $this->rowToColumns($row, $query);
			$objects[$key] = $this->createObject($columns);
		}
		
		foreach ($this->refersTo as $assoc) {
			if (empty($assoc->load)) continue;
			
			$objects = $assoc->loadForObjects($objects);
		}
		
		return $objects;
	}
	
	function rowToColumns($row, &$query) {
		$columns = array();
		$values = array_values($row);
		
		foreach ($row as $key => $value) {
			if (strpos($key, '.') !== false) {
				$col = $query->parseColumn($key);
				$value = $col->valueForColumn($value);
				$key = $col->columnName();
			} else {
				$key = $this->objectColumnName($key);
			}
			$columns[$key] = $value;
		}
		
		return $columns;
	}
	
	function save(&$obj) {
		$columns = $this->collectValues($obj, Kiosk_INCLUDE_PRIMARY_KEYS);
		
		$inserted = $this->table->insert($columns);
		return ($inserted > 0);
	}
	
	function destroy(&$obj) {
		return trigger_error(KIOSK_ERROR_CONFIG. 'cannot destroy object without primary key');
	}
	
	function fetch(&$obj, $name, $params) {
		if (is_string($name)) {
			$assoc =& $this->associationWithName($name);
			if ($assoc) {
				return $assoc->fetch($obj, $params);
			}
		}

		return $this->fetchColumn($obj, $name, $params);
	}
	
	function fetchColumn(&$obj, $name, $params) {
		$id = $this->getId($obj);
		if (!$id) return null;
		
		$params += array(
			'columns' => (array) $name, 
			'conditions' => $this->conditionForPrimaryKey($id), 
			'first' => true, 
		);
		
		$loaded = (array) $this->find($params);
		
		foreach ((array) $name as $key) {
			if (!isset($obj->$key)) {
				$obj->$key = $loaded[$key];
			}
		}
		
		if (is_array($name)) {
			return $loaded;
		}
		
		return $loaded[$name];
	}
	
	function paramsToFetch(&$obj, $name) {
		$assoc =& $this->associationWithName($name);
		if (! $assoc) return null;
		
		return $assoc->paramsToFetch($obj);
	}
	
	function _beforeSave($schema, &$obj) {
		foreach ($schema->refersTo as $assoc) {
			$assoc->beforeSave($obj);
		}
	}
	
	// finalize
	
	function finalize() {
		if (empty($this->columns)) {
			$this->columns = array_keys($this->table->describe());
		}
		
		if (empty($this->defaults)) {
			$this->defaults = array();
		}
		
		$this->buildCallbacks();
		
		$this->buildColumnsMap();
		
		$this->parseAssociation('refersTo');
		$this->parseAssociation('belongsTo');
		$this->parseAssociation('hasOne');
		$this->parseAssociation('hasMany');
		
		$this->refersTo += $this->belongsTo;
		unset($this->belongsTo);
	}
	
	function buildCallbacks() {
		foreach (array('beforeSave', 'afterFetch', 'afterLoad') as $callback) {
			if (! empty($this->$callback)) {
				if (!is_a($this->$callback, 'Kiosk_Callable')) {
					return trigger_error(KIOSK_ERROR_CONFIG. "cannot call \$this->{$callback}");
				}
			} else {
				$this->$callback = null;
			}
		}
	}
	
	function buildColumnsMap() {
		$this->db_columns = array();
		$this->obj_columns = array();
		
		foreach ($this->columns as $obj_name => $db_name) {
			if (is_integer($obj_name)) {
				$obj_name = $db_name;
			}
			
			$this->db_columns[$obj_name] = $db_name;
			$this->obj_columns[$db_name] = $obj_name;
		}
	}
	
	
	/*
		関連の定義情報を解析して、利用しやすい形にまとめる
	*/
	function parseAssociation($type) {
		if (empty($this->$type)) {
			$this->$type = array();
			return;
		}
		
		$items = array();
		
		foreach ((array) $this->$type as $class=>$info) {
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
			
			$assoc = Kiosk_Association::create($type, $this->class, $info);
			
			if (is_null($assoc)) {
				trigger_error(KIOSK_ERROR_CONFIG. "{$type} association definition for class {$this->class} is invalid.");
				continue;
			}
			
			$items[$assoc->name] = $assoc;
		}
		
		$this->$type = $items;
	}
}

require_once KIOSK_LIB_DIR. '/data/sources/db/schemas/NoPK.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/schemas/SinglePK.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/schemas/MultiPK.php';

