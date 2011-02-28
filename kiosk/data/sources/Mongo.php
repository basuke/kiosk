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
	protected $schemas = array();
	
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
		
		$this->schemas[strtolower($class)] = $schema;
		
		return $schema;
	}
	
	public function schemaForClass($class) {
		$key = strtolower($class);
		
		return (isset($this->schemas[$key]) ? $this->schemas[$key] : null);
	}
	
	public function collectionForClass($class) {
		$schema = $this->schemaForClass($class);
		return ($schema ? $schema->name : null);
	}
	
	public function classForCollection($collection) {
		foreach ($this->schemas as $schema) {
			if ($schema->name == $collection) {
				return $schema->class;
			}
		}
		return null;
	}
}

class Kiosk_Data_Schema_Mongo extends Kiosk_Data_Schema {
	protected $collection;
	public $columns = array();
	
	public function __construct($class, $source, $params) {
		parent::__construct($class, $source, $params);
		
		extract($params);
		
		$this->collection = $source->db->$name;
	}
	
	protected function defaultColumnDef($name) {
		return array(
			'name' => $name, 
			'type' => null, 
			'_ref' => false, 
		);
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
			
			$def += $this->defaultColumnDef($key);
			
			switch ($def['type']) {
				case 'string':
				case 'text':
				case 'str':
					$def['type'] = 'string';
					break;
					
				case 'integer':
				case 'int':
					$def['type'] = 'integer';
					break;
					
				case 'double':
				case 'float':
					$def['type'] = 'double';
					break;
					
				case 'boolean':
				case 'bool':
					$def['type'] = 'boolean';
					break;
					
				case 'array':
					$def['type'] = 'array';
					break;
					
				case 'object':
					$def['type'] = 'object';
					break;
					
				case 'entity':
				case 'hasMany':
				case 'hasOne':
					$def['_ref'] = true;
					break;
					
				default:
					$def['_ref'] = true;
					break;
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
	public function save($entity) {
		$doc = $this->entityToDocument($entity);
		if (is_null($doc)) {
			return false;
		}
		
		$this->collection->save($doc);
		
		if (empty($entity->id)) {
			$entity->id = strval($doc['_id']);
		}
		
		return true;
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
		
		$conditions = $query->parseConditions($conditions);
		if (! $conditions) $conditions = array();
		
		$columns = $query->columns;
		if (! $columns) $columns = array();
		
		$cursor = $this->collection->find($conditions, $columns);
		
		return iterator_to_array($cursor, false);
	}
	
	public function rowsToObjects($rows, $query) {
		$entities = parent::rowsToObjects($rows, $query);
		
		return $entities;
	}
	
	public function rowToColumns($row, $query) {
		$row['id'] = strval($row['_id']);
		unset($row['_id']);
		
		foreach ($this->columns as $key => $def) {
			$name = $def['name'];
			
			if (isset($row[$name])) {
				$value = $row[$name];
				unset($row[$name]);
				$row[$key] = $value;
			}
			
			if (!empty($def['load'])) {
				$row[$key] = $this->resolveDBRef($row[$key]);
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
	
	public function fetch($entity, $name, $params) {
		$def = $this->columnDef($name);
		$value = null;
		
		if ($def['_ref']) {
			$value = $this->fetchReference($entity, $def, $params);
		}
		
		return $value;
	}
	
	protected function fetchReference($entity, $def, $params) {
		$name = $def['name'];
		
		$value = $entity->$name;
		
		if (!$value) return null;
		
		switch ($def['type']) {
			case 'hasMany':
				$value = $this->resolveDBRef($value);
				break;
				
			case 'hasOne':
				$value = $this->resolveDBRef($value);
				break;
				
			case 'entity':
				$value = $this->resolveDBRef($value);
				$entity->$name = $value;
				break;
				
			default:
				if (is_a($value, 'MongoId')) {
					$schema = $this->source->schemaForClass($def['type']);
					$value = $schema->load($value, $params);
				} else {
					$value = $this->resolveDBRef($value);
				}
				
				$entity->$name = $value;
				break;
		}
		
		return $value;
	}
	
	public function columnDef($name) {
		foreach ($this->columns as $key => $def) {
			if ($name == $key) {
				return $def;
			}
		}
		
		return $this->defaultColumnDef($name);
	}
	
	public function toDocumentColumnName($name) {
		$pos = strpos($name, '.');
		if ($pos !== false) {
			return
				$this->toDocumentColumnName(substr($name, 0, $pos)). 
				substr($name, $pos);
		}
		
		$def = $this->columnDef($name);
		return $def['name'];
	}
	
	/*
		エンティティをドキュメントに変換する。
		ドキュメントの整合性をチェックを行い、失敗した場合は
		'KIOSK:RUNTIME:VALIDATION_FAIL' エラーを起こし、nullを返す
	*/
	protected function entityToDocument($entity) {
		$doc = (array) $entity;
		
		if (!empty($doc['id'])) {
			$doc['_id'] = new MongoId($doc['id']);
			unset($doc['id']);
		}
		
		foreach ($this->columns as $key => $def) {
			if (!isset($doc[$key])) {
				continue;
			}
			
			$value = $doc[$key];
			unset($doc[$key]);
			
			$value = $this->coerceDocumentValue($def, $value);
			$name = $def['name'];
			
			$doc[$name] = $value;
		}
		
		return $doc;
	}
	
	protected function coerceDocumentValue($def, $value) {
		switch ($def['type']) {
			case null:	// 変換なし
				return $value;
				
			case 'string':
				return strval($value);
				
			case 'integer':
				return intval($value);
				
			case 'double':
				return floatval($value);
				
			case 'boolean':
				if (is_string($value)) {
					if (ctype_digit($value)) {
						$value = intval($value);
					} else {
						$value = preg_match('/^(on|true|yes)$/i', $value);
					}
				}
				return (bool) $value;
				
			case 'array':
				return (array) $value;
				
			case 'object':
				if (!is_object($value) and !is_array($value)) {
					trigger_error(KIOSK_ERROR_RUNTIME. 
								'type mismatch');
					return null;
				}
				return $value;
				
			case 'entity':
				if (!is_object($value)) {
					trigger_error(KIOSK_ERROR_RUNTIME. 
								'type mismatch');
					return null;
				}
				return $this->createDBRef($value);
		}
		
		// 後はエンティティクラス名が指定されているはず
		$class = $def['type'];
		$schema = $this->source->schemaForClass($class);
		if (! $schema) {
			trigger_error(KIOSK_ERROR_RUNTIME. 'unknown type:'. $class);
			return null;
		}
		
		$value = $this->ensureSaved($value);
		if (empty($value)) {
			trigger_error(KIOSK_ERROR_RUNTIME. 'cannnot save reference for non-saved entity');
			return null;
		}
		
		return new MongoId($value->id);
	}
	
	protected function ensureSaved($entity) {
		if (empty($entity->id)) {
			Kiosk_save($entity);
			
			if (empty($entity->id)) {
				trigger_error(KIOSK_ERROR_RUNTIME. 'cannnot create reference for non-saved entity');
				return null;
			}
		}
		return $entity;
	}
	
	public function createDBRef($entity) {
		$class = get_class($entity);
		$collection = $this->source->collectionForClass($class);
		if (!$collection) {
			trigger_error(KIOSK_ERROR_RUNTIME. 'cannnot create non-bound entity');
			return null;
		}
		
		$entity = $this->ensureSaved($entity);
		if (empty($entity)) {
			trigger_error(KIOSK_ERROR_RUNTIME. 'cannnot create reference for non-saved entity');
			return null;
		}
		
		return MongoDBRef::create($collection, new MongoId($entity->id));
	}
	
	public function resolveDBRef($ref) {
		$collection = $ref['$ref'];
		$class = $this->source->classForCollection($collection);
		if (!$class) {
			return MongoDBRef::get($this->collection->db, $ref);
		}
		return Kiosk_load($class, $ref['$id']);
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
	
	public function buildCondition($name, $op, $value) {
		$name = $this->_schema->toDocumentColumnName($name);
		
		if (!empty(self::$operators[$op])) {
			$op = $this->mongoOp(self::$operators[$op]);
			
			return array($name => array($op => $value));
		}
		
		switch ($op) {
			case '=':
				return array($name => $value);
		}
		
		return array($op => array($name, $value));
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

