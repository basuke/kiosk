<?php

class SampleMongo {
	public $users = array(
		array('name' => 'Taro', 'age' => 40, 'tags' => array()), 
		array('name' => 'Jiro', 'age' => 35, 'tags' => array('iPhone')), 
		array('name' => 'Saburo', 'age' => 30, 'tags' => array('Mac', 'iPod', 'iPhone')), 
		
		array('name' => 'Hanako', 'age' => 32, 'company' => array('name' => 'Kodansha', 'address' => 'Tokyo')), 
		array('name' => 'Sachiko', 'age' => 28, 'female' => true), 
		array('name' => 'Mei', 'age' => 5, 'female' => true, 'tags' => array('corn')), 
	);
	
	public $dbname = 'test';
	public $source;
	
	public function __construct() {
		$this->source = Kiosk::source(
			'mongo', 
			array(
				'type' => 'Mongo', 
				'dbname'=> $this->dbname, 
			)
		);
	}
	
	public function db() {
		return $this->source->db;
	}
	
	public function cleanup() {
		$collection = $this->collection('user');
		$collection->drop();
		
		$this->ids = array();
	}
	
	public function collection($name) {
		return $this->db()->$name;
	}
	
	public function load($colletion, $id) {
		$query = array('_id' => new MongoId($id));
		return $this->collection($colletion)->findOne($query);
	}
	
	public function all($collection, $params=array()) {
		$cursor = $this->collection($collection)->find($params);
		return iterator_to_array($cursor);
	}
	
	public function bulkInsert($collection, $data, $map = array()) {
		$collection = $this->collection($collection);
		$ids = array();
		
		foreach ($data as $entity) {
			$entity = $this->mapEntity($entity, $map);
			$collection->insert($entity);
			
			$ids[] = strval($entity['_id']);
		}
		
		return $ids;
	}
	
	public function mapEntity($entity, $map) {
		$converted = array();
		foreach ($entity as $key => $value) {
			if (!empty($map[$key])) {
				$key = $map[$key];
			}
			
			$converted[$key] = $value;
		}
		return $converted;
	}
	
	public function env1() {
		$data= array_slice($this->users, 0, 3);
		return $this->bulkInsert('user', $data);
	}
	
	public function env2() {
		$data= array_slice($this->users, 0, 6);
		return $this->bulkInsert('user', $data);
	}
	
	public function env3() {
		$data= array_slice($this->users, 0, 6);
		$map = array(
			'name' => 'n', 
			'age' => 'a', 
			'tags' => 't', 
			'company' => 'c', 
		);
		return $this->bulkInsert('user', $data, $map);
	}
}

