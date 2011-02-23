<?php

class SampleMongoEnv {
	public $users = array(
		array('name' => 'Taro', 'age' => 40, 'tags' => array()), 
		array('name' => 'Jiro', 'age' => 45, 'tags' => array('iPhone')), 
		array('name' => 'Saburo', 'age' => 30, 'tags' => array('Mac', 'iPod')), 
	);
	
	public $dbname = 'kioskTest';
	public $mongo;
	
	public function __construct($env=null) {
		$this->mongo = new Mongo();
		
		if ($env) {
			$this->$env();
		}
	}
	
	function db() {
		$dbname = $this->dbname;
		return $this->mongo->$dbname;
	}
	
	function collection($name) {
		return $this->db()->$name;
	}
	
	function load($colletion, $id) {
		$query = array('_id' => new MongoId($id));
		return $this->collection($colletion)->findOne($query);
	}
	
	function source() {
		$dbname = $this->dbname;
		
		$source = Kiosk::source(
			'mongo', 
			array(
				'type' => 'Mongo', 
				'dbname'=> $dbname, 
			)
		);
		
		return $source;
	}
	
	function env1() {
		$collection = $this->collection('user');
		
		$collection->drop();
		
		$this->ids = array();
		
		foreach ($this->users as $user) {
			$collection->insert($user);
			
			$this->ids[] = strval($user['_id']);
		}
	}
}

