<?php

require_once KIOSK_HOME. '/tests/samples/Mongo.php';
require_once KIOSK_HOME. '/tests/samples/Classes.php';

class Kiosk_Data_MongoSourceTestCase extends UnitTestCase {
	function testCreate() {
		$env = new SampleMongoEnv('env1');
		
		$source = $env->source();
		User::bind($source, array());
		
		// Userを作成
		$user = User::create();
		$this->assertIsA($user, 'User');
		$this->assertNull($user->id);
		
		$user->name = 'Taro';
		$user->save();
		$this->assertNotNull($user->id);
		
		$data = $env->collection('users');
	}
	
	function testBasicFind() {
		$env = new SampleMongoEnv('env1');
		
		$source = $env->source();
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

