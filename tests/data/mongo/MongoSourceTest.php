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
		
		$user->name = 'Mokeko';
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
		
		// 複数をロード
		$users = User::load($ids);
		$this->assertEqual(count($users), 3);
		
		$user = $users[$ids[0]];
		$this->assertEqual($user->name, 'Taro');
		$this->assertEqual($user->age, 40);
	}
	
	public function testUpdate() {
		$user = User::create(array('name'=>'Mokeko', 'age'=>30));
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
	
	public function testOrder() {
		// ユーザーの準備
		$this->sample->env2();
		
		$user = User::find(array(
			'first', 
			'order' => array('age')
		));
		$this->assertEqual($user->name, 'Mei');
		
		$user = User::find(array(
			'first', 
			'order' => array('-age')
		));
		$this->assertEqual($user->name, 'Taro');
	}
	
	public function testOffsetLimit() {
		// ユーザーの準備
		$this->sample->env2();
		
		$users = User::find(array(
			'order' => array('age'), 
			'offset' => 0, 
			'limit' => 2, 
		));
		$this->assertEqual(count($users), 2);
		$this->assertEqual($users[1]->name, 'Sachiko');
		
		$users = User::find(array(
			'order' => array('age'), 
			'offset' => 2, 
			'limit' => 2, 
		));
		$this->assertEqual(count($users), 2);
		$this->assertEqual($users[1]->name, 'Hanako');
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
	
	public function testColumnMapping() {
		// ユーザーの準備
		$ids = $this->sample->env3();
		
		User::bind($this->source, array(
			'columns' => array(
				'name' => 'n', 
				'age' => array(
					'name' => 'a', 
					'type' => 'integer', 
				), 
				'company' => array(
					'name' => 'c', 
					'type' => 'object', 
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
		
		$user = User::find(array(
			'first', 
			'conditions' => array(
				'company.address' => 'Tokyo', 
			),
		));
		$this->assertEqual($user->name, 'Hanako');
		
		// ソートキーが変換されていることを確認する
		
		$user = User::find(array('first', 'order'=>array('age')));
		$this->assertEqual($user->name, 'Mei');
	}
	
	public function testColumnTyping() {
		User::bind($this->source, array(
			'columns' => array(
				'name' => array(
					'name' => 'n', 
					'type' => 'string', 
				), 
				'age' => array(
					'name' => 'a', 
					'type' => 'integer', 
				), 
				'weight' => array(
					'name' => 'w', 
					'type' => 'double', 
				), 
				'marriaged' => array(
					'name' => 'm', 
					'type' => 'boolean', 
				), 
				'tags' => array(
					'name' => 't', 
					'type' => 'array', 
				), 
				'company' => array(
					'name' => 'c', 
					'type' => 'object', 
				), 
			)
		));
		
		// セーブ時の変換がされていることを確認する
		
		$user = User::create();
		$user->name = 12345;
		$user->age = '31';
		$user->weight = '60';
		$user->marriaged = 'true';
		$user->tags = 'iPhone'; // array の場合、値が配列に変換される
		$user->save();
		
		$raw_data = $this->sample->load('user', $user->id);
		$this->assertEqual($raw_data['n'], '12345');
		$this->assertEqual($raw_data['a'], 31);
		$this->assertEqual($raw_data['w'], 60.0);
		$this->assertEqual($raw_data['m'], true);
		$this->assertEqual($raw_data['t'], array('iPhone'));
		
		$this->assertTrue(is_string($raw_data['n']));
		$this->assertTrue(is_integer($raw_data['a']));
		$this->assertTrue(is_double($raw_data['w']));
		$this->assertTrue(is_bool($raw_data['m']));
		$this->assertTrue(is_array($raw_data['t']));
		
		// オブジェクト型が指定されている場合に、
		// nullかオブジェクト以外をセットするとエラーになる
		
		$user->company = null;
		$user->save();	// 問題なし
		
		$user->company = array('name' => '10gen');
		$user->save();	// 問題なし
		
		$user->company = '10gen';
		$this->expectError();
		$user->save();
	}
}

class Kiosk_Data_MongoSourceReferencesTestCase extends UnitTestCase {
	public function setUp() {
		Kiosk_reset();
		
		$this->sample = new SampleMongo();
		$this->sample->cleanup();
		
		$this->source = $this->sample->source;
	}
	
	public function testReference() {
		// 構造の定義
		
		User::bind($this->source, array(
		));
		
		Item::bind($this->source, array(
			'columns' => array(
				'user' => array(
					'type' => 'entity', // カラムはDBRef
				), 
			)
		));
		
		// エンティティの作成時にDBRefが作成されることを確認
		
		$taro = User::create(array(
			'name' => 'Taro', 
		));
		$taro->save();
		
		$mba = Item::create(array(
			'user' => $taro, 
			'title' => 'MacBook Air', 
		));
		$mba->save();
		
		$raw_data = $this->sample->load('item', $mba->id);
		$this->assertEqual($raw_data['title'], $mba->title);
		$this->assertTrue(is_array($raw_data['user']));
		
		$user = MongoDBRef::get($this->sample->db(), $raw_data['user']);
		$this->assertEqual($user['name'], 'Taro');
		
		// ロードされたエンティティからカラムを
		// フェッチした場合にDBRefが正しく参照されることを確認
		
		$item = Item::load($mba->id);
		$item->fetch('user');
		$this->assertEqual($item->user->name, 'Taro');
	}
	
	public function testAutoLoadReference() {
		// 構造の定義
		
		User::bind($this->source, array(
		));
		
		Item::bind($this->source, array(
			'columns' => array(
				'user' => array(
					'type' => 'entity', // カラムはDBRef
					'load' => true,		// 自動で読み込まれる
				), 
			)
		));
		
		// データの準備
		
		list($taro, ) = User::import(array('name' => 'Taro'));
		list($mba, ) = Item::import(array(
			'user' => $taro, 
			'title' => 'MacBook Air', 
		));
		
		// 自動的にロードされることを確認
		
		$item = Item::load($mba->id);
		$this->assertEqual($item->user->name, 'Taro');
	}
	
	public function testHasManyReference() {
		// 構造の定義
		
		User::bind($this->source, array(
			'columns' => array(
				'items' => array(
					'type' => 'hasMany', 
					'class' => 'Item', 
				), 
			)
		));
		
		Item::bind($this->source, array(
			'columns' => array(
				'user' => array(
					'type' => 'User', // カラムはDBRef
				), 
			)
		));
		
		// データの準備
		
		list($taro, ) = User::import(array('name' => 'Taro'));
		
		Item::import(array(
			array('user' => $taro, 'title' => 'MacBook Air'), 
			array('user' => $taro, 'title' => 'MacBook Pro'), 
			array('user' => $taro, 'title' => 'iPhone'), 
		));
		
		// ユーザーをロードする
		
		$item = Item::find(array(
			'first', 
			'conditions'=>array('title' => 'iPhone'), 
		));
		
		$this->assertNotNull($item);
		
		$item->fetch('user');
		$this->assertEqual($item->user->name, 'Taro');
		
		// ユーザーに関連するアイテムをロードする
		
		$items = $taro->fetch('items');
		$this->assertEqual(count($items), 3);
	}
}

