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
}

class Kiosk_Schema_DB_NoPrimaryKeys extends Kiosk_Schema {
	function fetchColumn(&$obj, $name, $params) {
		return trigger_error(KIOSK_ERROR_CONFIG. 'cannot fetch column without primary key');
	}
}

class Kiosk_Schema_DB_SinglePrimaryKey extends Kiosk_Schema {
	function load($id, $params) {
		$query = $this->createQuery();
		$query->setParams($params);
		$rows = $this->table->load($id, $query->$params);
		
		if (is_array($id) == false) {
			if ($rows == null) return null;
			
			$rows = array($rows);
		}
		
		$objects = $query->rowsToObjects($rows);
		if ($this->afterLoad) {
			$this->applyFilter($objects, $this->afterLoad);
		}
		
		if (is_array($id) == false) {
			return array_first($objects);
		}
		
		return $objects;
	}
	
	function save(&$obj) {
		$id = $this->getId($obj);
		
		// 新規保存か？
		if (is_null($id)) {
			// 初期値を埋める
			$data =& Kiosk_data();
			$data->apply($obj, $this->defaultValues(), false);
			
			// 保存前に行う処理を実行する
			$this->_beforeSave($this, $obj);
			
			// 保存用の値のハッシュを取得する
			$columns = $this->collectValues($obj, Kiosk_INCLUDE_PRIMARY_KEYS);
			
			// テーブルに保存
			$success = $this->table->insert($columns);
			if (!$success) {
				return trigger_error(KIOSK_ERROR_RUNTIME. 'insert failed');
			}
			
			// 新規IDをオブジェクトにセット
			$this->setId($obj, $this->table->lastId());
		} else {
			// 保存前に行う処理を実行する
			$this->_beforeSave($this, $obj);
			
			$columns = $this->collectValues($obj, Kiosk_WITHOUT_PRIMARY_KEYS);
			
			$this->table->update($columns, $this->conditionForPrimaryKey($id));
		}
		
		return true;
	}
	
	function destroy(&$obj) {
		$id = $this->getId($obj);
		if (is_null($id)) {
			return trigger_error(KIOSK_ERROR_RUNTIME. 'cannnot destroy unsaved object');
		}
		
		// テーブルから削除
		$count = $this->table->delete($this->conditionForPrimaryKey($id));
		if (!$count) {
			return trigger_error(KIOSK_ERROR_RUNTIME. 'delete failed');
		}
		
		// オブジェクトIDをnullにセット
		$this->setId($obj, null);
		return true;
	}
	
}

class Kiosk_Schema_DB_MultiPrimaryKeys extends Kiosk_Schema {
	function load($id, $params) {
		$condition = $id;
		
		$multi = is_pure_array($condition);
		
		if ($multi) {
			$list = $condition;
			
			if (empty($list)) return array();
			
			// 主キーが一つだけの場合には、例外として値だけの配列も認める
			if (!is_array($list[0]) and count($this->primaryKeys) == 1) {
				$params['conditions'] = array($this->primaryKeys[0] => $list);
			} else {
				$params['conditions'] = array('OR' => $list);
			}
		} else {
			$params['conditions'] = $condition;
			$params[] = 'first';
		}
		
		return $this->find($params);
	}
	
	function save(&$obj) {
		$condition = $this->conditionsForPrimaryKeys($obj);
		$columns = $this->collectValues($obj, Kiosk_WITHOUT_PRIMARY_KEYS);
		
		$updated = $this->table->update($columns, $condition);
		if ($updated > 0) return true;
		
		$columns = $this->collectValues($obj, Kiosk_INCLUDE_PRIMARY_KEYS);
		
		$inserted = $this->table->insert($columns);
		return ($inserted > 0);
	}
	
	function destroy(&$obj) {
		$condition = $this->conditionsForPrimaryKeys($obj);
		$deleted = $this->table->delete($condition);
		return ($deleted > 0);
	}
	
	function conditionsForPrimaryKeys($obj) {
		assert('$this->primaryKeys and is_array($this->primaryKeys)');
		
		$conditions = array();
		
		foreach ($this->primaryKeys as $column) {
			$conditions[$column] = $obj->$column;
		}
		
		$query = $this->createQuery();
		return $query->parseConditions($conditions);
	}
	
}

