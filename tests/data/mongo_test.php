<?php

require_once KIOSK_HOME. '/tests/samples/Mongo.php';
require_once KIOSK_HOME. '/tests/samples/Classes.php';

class Kiosk_Mongo_TestCase extends UnitTestCase {
	function testBasicCrud() {
		$sample = new SampleMongoEnv();
		$sample->env1();
		$dbname = $sample->dbname;
		
		$source = Kiosk::source(
			'mongo', 
			array(
				'type' => 'Mongo', 
				'dbname'=> $dbname, 
			)
		);
		
		User::bind($source, array());
		
		// すべてのオブジェクトを取得
		$users = User::find();
		$this->assertEqual(count($users), 3);
		$this->assertIsA($users[0], 'User');
		
		// Jiroを検索
		$users = User::find(array(
			'conditions'=>array(
				'name' => 'Jiro', 
			), 
		));
		$this->assertEqual(count($users), 1);
		$this->assertEqual($users[0]->name, 'Jiro');
	}
}

