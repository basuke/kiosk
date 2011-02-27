<?php

require_once KIOSK_HOME. '/tests/samples/Mongo.php';
require_once KIOSK_HOME. '/tests/samples/Classes.php';

class Kiosk_Data_MongoSourceCRUDTestCase extends UnitTestCase {
	public function setUp() {
		Kiosk_reset();
		
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
		$ids = $this->sample->env1();
		
		$id = $ids[0];
		
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
		$ids = $this->sample->env1();
		
		$this->assertEqual(count(User::find()), 3);
		
		$id = $ids[0];
		
		$user = User::load($id);
		$user->destroy();
		$this->assertNull($user->id);
		$this->assertEqual(count(User::find()), 2);
	}
	
	public function testFind() {
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
		
		// TagでMacを検索
		$users = User::find(array(
			'conditions' => array(
				'tags' => 'Mac'
			),
		));
		
		$this->assertEqual(count($users), 1);
		$this->assertEqual($users[0]->name, 'Saburo');
		
		// 年齢が33歳以上で、TagにiPhoneを持つものを検索
		$user = User::find(array(
			'first', 
			'conditions' => array(
				'tags' => 'iPhone', 
				'age >=' => 33,
			),
		));
		
		$this->assertEqual($user->name, 'Jiro');
	}
	
	public function testCount() {
		// ユーザーの準備
		$this->sample->env1();
		
		// すべてのオブジェクトの個数
		$this->assertEqual(User::count(), 3);
		
		// Jiroを含む個数
		$this->assertEqual(User::count(array(
			'conditions'=>array(
				'name' => 'Jiro', 
			), 
		)), 1);
	}
	
	public function testComparisonOperators() {
		// ユーザーの準備
		$this->sample->env2();
		
		$this->assertEqual(User::count(array(
			'conditions'=>array(
				'age =' => 30, 
			), 
		)), 1);
		
		$this->assertEqual(User::count(array(
			'conditions'=>array(
				'age >' => 30, 
			), 
		)), 3);
		
		$this->assertEqual(User::count(array(
			'conditions'=>array(
				'age >=' => 30, 
			), 
		)), 4);
		
		$this->assertEqual(User::count(array(
			'conditions'=>array(
				'age <' => 30, 
			), 
		)), 2);
		
		$this->assertEqual(User::count(array(
			'conditions'=>array(
				'age <=' => 30, 
			), 
		)), 3);
		
		// 複合
		$this->assertEqual(User::count(array(
			'conditions'=>array(
				'age >' => 30, 
				'age <=' => 35, 
			), 
		)), 2);
	}
}

class Kiosk_Data_MongoSourceSchemaTestCase extends UnitTestCase {
	public function setUp() {
		Kiosk_reset();
		
		$this->sample = new SampleMongo();
		$this->sample->cleanup();
		
		$this->source = $this->sample->source;
	}
	
	function testColumnMapping() {
		// ユーザーの準備
		$ids = $this->sample->env3();
		
		User::bind($this->source, array(
			'columns' => array(
				'name' => 'n', 
				'age' => array(
					'name' => 'a', 
					'type' => 'integer', 
				), 
			)
		));
		
		// ロード時の変換がされていることを確認する
		
		$taro = User::load($ids[0]);
		$this->assertEqual($taro->name, 'Taro');
		$this->assertEqual($taro->age, 40);
		
		// セーブ時の変換がされていることを確認する
		
		$user = User::create();
		$user->name = 'John';
		$user->age = 31;
		$user->save();
		
		$raw_data = $this->sample->load('user', $user->id);
		$this->assertEqual($raw_data['n'], 'John');
		$this->assertEqual($raw_data['a'], 31);
		
		// 検索キーの値が変換されていることを確認する
		
		$users = User::find(array(
			'conditions' => array(
				'age >' => 30
			),
		));
		$this->assertEqual(count($users), 4);
	}
}

