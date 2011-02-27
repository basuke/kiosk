<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';

class Kiosk_Data_Source_Mongo extends Kiosk_Data_Source {
	/* static */
	function &open($config) {
		$source = new Kiosk_Data_Source_Mongo($config);
		return $source;
	}
	
	public $conn;
	public $db;
	
	public function __construct($config) {
		$host = 'localhost';
		$port = 13174;
		$dbname = null;
		extract($config);
		
		$this->conn = new Mongo($host, $port);
		$this->db = $this->conn->$dbname;
	}
	
	// schema creation
	
	public function buildSchema($class, $params) {
		extract($params, EXTR_SKIP);
		
		if (empty($name)) {
			$namer = Kiosk::namer();
			$params['name'] = $namer->classNameToTableName($class);
		}
		
		$schema = new Kiosk_Data_Schema_Mongo($class, $this, $params);
		return $schema;
	}
}

class Kiosk_Data_Schema_Mongo extends Kiosk_Data_Schema {
	protected $collection;
	protected $columns = array();
	
	public function __construct($class, $source, $params) {
		parent::__construct($class, $source, $params);
		
		extract($params);
		
		$this->collection = $source->db->$name;
	}
	
	public function finalize() {
		$columns = array();
		
		foreach ($this->columns as $key => $def) {
			if (is_integer($key)) {
				$key = $def;
			}
			
			if (is_string($def)) {
				$def = array(
					'name' => $def, 
				);
			}
			
			if (empty($def['name'])) {
				$def['name'] = $key;
			}
			
			$columns[$key] = $def;
		}
		
		$this->columns = $columns;
	}
	
	/*
		オブジェクトをロードする
	*/
	public function load($id, $params) {
		$multi = is_array($id);
		
		$entities = $this->loadMulti((array) $id, $params);
		
		return $multi ? $entities : array_first($entities);
	}
	
	protected function loadMulti($id_list, $params) {
		if (empty($id_list)) return array();
		
		$object_id_list = array();
		foreach ($id_list as $id) {
			$object_id_list[] = new MongoId($id);
		}
		
		$params = array(
			'conditions' => array('_id' => $object_id_list)
		);
		
		$entities = array();
		foreach ($this->find($params) as $entity) {
			$id = $entity->id;
			$entities[$id] = $entity;
		}
		
		$sorted = array();
		foreach ($id_list as $id) {
			$sorted[$id] = $entities[$id];
		}
		
		return $sorted;
	}
	
	/*
		オブジェクトを保存する
	*/
	public function save($obj) {
		$data = (array) $obj;
		
		if (!empty($data['id'])) {
			$data['_id'] = new MongoId($data['id']);
			unset($data['id']);
		}
		
		$this->collection->save($data);
		
		if (empty($obj->id)) {
			$obj->id = strval($data['_id']);
		}
	}
	
	public function destroy($obj) {
		$id = $obj->id;
		if (is_null($id)) {
			return trigger_error(KIOSK_ERROR_RUNTIME. 'cannnot destroy unsaved object');
		}
		
		// テーブルから削除
		$this->collection->remove(array('_id' => new MongoId($id)));
		
		// オブジェクトIDをnullにセット
		$obj->id = null;
		return true;
	}
	
	public function queryClass() {
		return 'Kiosk_Data_Query_Mongo';
	}
	
	/*
		オブジェクトを検索する
	*/
	public function findWithQuery($query) {
		$conditions = $query->conditions;
		if (! $conditions) $conditions = array();
		
#var_dump($conditions, $query->parseConditions($conditions));
		$conditions = $query->parseConditions($conditions);
		if (! $conditions) $conditions = array();
		
		$columns = $query->columns;
		if (! $columns) $columns = array();
		
		$cursor = $this->collection->find($conditions, $columns);
		
		return iterator_to_array($cursor, false);
	}
	
	public function rowToColumns($row, $query) {
		$row['id'] = strval($row['_id']);
		unset($row['_id']);
		
		foreach ($this->columns as $key => $def) {
			$name = $def['name'];
			
			if (isset($row[$name])) {
				$row[$key] = $row[$name];
				unset($row[$name]);
			}
		}
		
		return $row;
	}
	
	public function countWithQuery($query) {
		$conditions = $query->conditions;
		if (! $conditions) $conditions = array();
		
		$conditions = $query->parseConditions($query->conditions);
		if (! is_array($conditions)) $conditions = array();
		
		return $this->collection->find($conditions, array())->count();
	}
}

class Kiosk_Data_Query_Mongo extends Kiosk_Data_Query {
	static private $operators = array(
		'IN' => 'in', 
		'>' => 'gt',
		'>=' => 'gte',
		'<' => 'lt',
		'<=' => 'lte',
	);
	
	public function buildCondition($key, $op, $value) {
		if (!empty(self::$operators[$op])) {
			$op = $this->mongoOp(self::$operators[$op]);
			
			return array($key => array($op => $value));
		}
		
		switch ($op) {
			case '=':
				return array($key => $value);
		}
		
		return array($op => array($key, $value));
	}
	
	public function joinConditions($conditions, $or) {
		assert('is_array($conditions)');
		
		if ($or) {
			return array($this->mongoOp('or'), $conditions);
		}
		
		return $this->mergeArrays($conditions);
	}
	
	private function mergeArrays($arrays) {
		$result = array();
		foreach ($arrays as $hash) {
			foreach ($hash as $key=>$value) {
				if (empty($result[$key])) {
					$result[$key] = $value;
				} else {
					$result[$key] = $this->mergeArrays(array($result[$key], $value));
				}
			}
		}
		
		return $result;
	}
	
	private function mongoOp($op) {
		$cmd = ini_get('mongo.cmd');
		return $cmd. $op;
	}
}

