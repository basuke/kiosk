<?php

require_once KIOSK_HOME. '/tests/samples/Mongo.php';
require_once KIOSK_HOME. '/tests/samples/Classes.php';

class Kiosk_Data_MongoSourceSimpleTestCase extends UnitTestCase {
	public function setUp() {
		$this->sample = new SampleMongo();
		$this->sample->cleanup();
		
		$this->source = $this->sample->source;
		User::bind($this->source, array());
	}
	
	public function testCreate() {
		// Userを作成
		$user = User::create();
		$this->assertIsA($user, 'User');
		$this->assertNull($user->id);
		
		$user->name = 'Hanako';
		$user->save();
		$this->assertNotNull($user->id);
		
		$raw_data = $this->sample->load('user', $user->id);
		$this->assertEqual($raw_data['name'], $user->name);
	}
	
	public function testLoad() {
		// ユーザーの準備
		$this->sample->env1();
		
		$id = $this->sample->ids[0];
		
		$user = User::load($id);
		$this->assertEqual($user->name, 'Taro');
		$this->assertEqual($user->age, 40);
	}
	
	public function testUpdate() {
		$user = User::create(array('name'=>'Hanako', 'age'=>30));
		$user->save();
		$id = $user->id;
		
		$user->age = 20;
		$user->save();
		
		$raw_data = $this->sample->load('user', $id);
		$this->assertEqual($raw_data['age'], $user->age);
	}
	
	public function testDestroy() {
		// ユーザーの準備
		$this->sample->env1();
		
		$this->assertEqual(count(User::find()), 3);
		
		$id = $this->sample->ids[0];
		
		$user = User::load($id);
		$user->destroy();
		$this->assertNull($user->id);
		$this->assertEqual(count(User::find()), 2);
	}
	
	function testBasicFind() {
		// ユーザーの準備
		$this->sample->env1();
		
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

