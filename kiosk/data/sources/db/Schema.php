<?php

require_once KIOSK_LIB_DIR. '/data/Schema.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/Query.php';
require_once KIOSK_LIB_DIR. '/data/sources/db/Association.php';

define('Kiosk_INCLUDE_PRIMARY_KEYS', false);
define('Kiosk_WITHOUT_PRIMARY_KEYS', true);

class Kiosk_Schema extends Kiosk_Data_Schema {
	var $class;		// クラス名
	var $name;		// テーブル名
	var $table;		// テーブルオブジェクト
	var $finalized;	// 正規化したか？
	
	var $columns;	// カラム定義。実際にはdb_columnsとobj_columnsにマップを作る
	
	var $db_columns;	// キーがオブジェクトのカラム名で値がDBのカラム名のマッピング
	var $obj_columns;	// キーがDBのカラム名で値がオブジェクトのカラム名のマッピング
	
	/*
		新規オブジェクトを作成する
		デフォルトの値で埋める
	*/
	function createObject($columns = array()) {
		return array_to_object($columns, $this->class);
	}
	
	function isSaved($obj) {
		return !empty($obj->id);
	}
	
	function getId($obj) {
		if (empty($obj->id)) return null;
		return $obj->id;
	}
	
	function setId(&$obj, $id) {
		$obj->id = $id;
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
			$column = $this->tableColumnName($key);
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
	
	function tableColumnName($name) {
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
	
	function fullTableColumnName($name) {
		$name = $this->tableColumnName($name);
		if (!$name) return null;
		
		return $this->table->fullColumnName($name);
	}
	
	function resolveColumnNamePath($path, &$associations) {
		assert('is_array($path)');
		assert('count($path) >= 1');
		assert('is_array($associations)');
		
		$name = array_shift($path);
		if (empty($path)) {
			return $this->fullTableColumnName($name);
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
	
	function &createQuery() {
		$query =& new Kiosk_Data_SchemaQuery();
		
		$query->setSchema($this);
		
		return $query;
	}
	
	function conditionForPrimaryKey($id) {
		return $this->table->conditionForPrimaryKey($id);
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
	
	function find($params) {
		if (is_string($params)) {
			$params = array('conditions' => $params);
		}
		
		if (array_test_option($params, 'first')) {
			$params['limit'] = 1;
		}
		
		$query = $this->createQuery();
		$query->setParams($params);
		$rows = $query->fetch();
		
		if (array_test_option($params, 'raw')) {
			if (array_test_option($params, 'first')) {
				return array_first($rows);
			}
			
			return $rows;
		}
		
		$objects = $query->rowsToObjects($rows);
		if ($this->afterLoad) {
			$this->applyFilter($objects, $this->afterLoad);
		}
		
		if (array_test_option($params, 'first')) {
			return array_first($objects);
		}
		
		return $objects;
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
		if ($this->finalized) return;
		
		$this->finalized = true;
		
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

