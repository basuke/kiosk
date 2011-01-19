<?php

class SampleMongoEnv {
	var $users = array(
		array('name' => 'Taro', 'age' => 40, 'tags' => array()), 
		array('name' => 'Jiro', 'age' => 45, 'tags' => array('iPhone')), 
		array('name' => 'Saburo', 'age' => 30, 'tags' => array('Mac', 'iPod')), 
	);
	
	var $dbname = 'kioskTest';
	
	function db() {
		$mongo = new Mongo();
		
		$dbname = $this->dbname;
		return $mongo->$dbname;
	}
	
	function env1() {
		$db = $this->db();
		
		$db->user->drop();
		foreach ($this->users as $user) {
			$db->user->insert($user);
		}
	}
}

