<?php

class SampleMongo {
	public $users = array(
		array('name' => 'Taro', 'age' => 40, 'tags' => array()), 
		array('name' => 'Jiro', 'age' => 45, 'tags' => array('iPhone')), 
		array('name' => 'Saburo', 'age' => 30, 'tags' => array('Mac', 'iPod', 'iPhone')), 
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
	
	public function env1() {
		$collection = $this->collection('user');
		
		$this->ids = array();
		
		foreach ($this->users as $user) {
			$collection->insert($user);
			
			$this->ids[] = strval($user['_id']);
		}
	}
}

