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
				'dbname'=> $dbname
			)
		);
		
		User::bind($source, array());
		
		$this->assertEqual(count(User::find()), 3);
	}
}

