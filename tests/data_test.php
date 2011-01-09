<?php

require_once 'Kiosk.php';

function _require_kiosk_test($path) {
	foreach (glob($path) as $path) {
		if (is_dir($path)) {
			_require_kiosk_test($path. '/*');
		} else if (file_exists($path)) {
			require_once $path;
		}
	}
}

_require_kiosk_test(dirname(__FILE__). '/data/*');

// Base class

class Kiosk_BaseData_TestCase extends UnitTestCase {
	function setUp() {
		Kiosk_reset();
		
		$this->backend =& Kiosk_backend();
		$this->data =& Kiosk_data();
	}
	
	function tearDown() {
		close_test_database($this->db);
		
		unset($this->db);
		unset($this->backend);
		unset($this->data);
		
		Kiosk_reset();
	}
}

// General usage

class User extends Kiosk {
}

class UserScore extends Kiosk {
}

class Item extends Kiosk {
}

class Bookmark extends Kiosk {
}

class Party extends Kiosk {
}

class Image extends Kiosk {
}

class Preference extends Kiosk {
}

class Kiosk_Data_TestCase extends Kiosk_BaseData_TestCase {
	function &schema1($dbname) {
		// setup database schema in SQLite memory db.
		
		if (file_exists($dbname)) {
			unlink($dbname);
		}
		
		$db =& open_test_database();
		
		$db->exec("
			CREATE TABLE person (
				id INTEGER PRIMARY KEY,
				name TEXT, 
				admin_flag BOOL, 
				age INTEGER, 
				created_at TIMESTAMP, 
				modified_at TIMESTAMP, 
				
				order_no INTEGER, 
				
				profile TEXT,
				col1 INTEGER, 
				col2 INTEGER, 
				col3 INTEGER, 
				
				image_id INTEGER, 
				party_id INTEGER
			)");
			
		$db->exec("
			CREATE TABLE person_result (
				person_id INTEGER NOT NULL,
				score INTEGER
			)");
			
		$db->exec("
			CREATE TABLE item (
				id INTEGER PRIMARY KEY,
				person_id INTEGER NOT NULL,
				title TEXT, 
				description TEXT, 
				created_at TIMESTAMP, 
				modified_at TIMESTAMP
			)");
			
		$db->exec("
			CREATE TABLE bookmark (
				id INTEGER PRIMARY KEY,
				person_id INTEGER NOT NULL, 
				item_id INTEGER NOT NULL
			)");
			
		$db->exec("
			CREATE TABLE party (
				id INTEGER PRIMARY KEY,
				name TEXT, 
				tag TEXT
			)");
		
		$db->exec("
			CREATE TABLE image (
				id INTEGER PRIMARY KEY,
				url TEXT
			)");
		
		$db->exec("
			CREATE TABLE preference (
				party_id INTEGER,
				name TEXT, 
				value TEXT
			)");
		
		// Setup fixtures
		$party = $db->table('party');
		$party->insert(array('name' => 'Republican Party', 'tag' => 're'));
		$party->insert(array('name' => 'Democratic Party', 'tag' => 'de'));
		
		$this->db =& $db;
		
		return $db;
	}
	
	function env1($dbname=':memory:', $schema_medhot='schema1') {
		Kiosk_reset();
		
		$db = $this->$schema_medhot($dbname);
		
		// setup Kiosk classes
		
		$timestamp_defaults = array(
			'created' => Kiosk::func('Kiosk', 'now'), 
			'modified' => Kiosk::func('Kiosk', 'now'), 
		);
		
		User::bind($db, array(
			'name' => 'person', 
			
			'columns' => array(
				'id', 
				'name', 
				'age', 
				'no' => 'order_no', 
				'is_admin' => 'admin_flag', 
				'created' => 'created_at', 
				'modified' => 'modified_at'
			),
			
			'defaults' => $timestamp_defaults,
			
			'validation' => array(
			),
			
			'hasMany' => array('Item', 'Bookmark'), 
			'refersTo' => array(
				'Party'=>array('load'=>true), 
				'Image'
			), 
		));
		
		Item::bind($db, array(
			'defaults' => $timestamp_defaults,
			
			'belongsTo' => 'User', 
		));
		
		Bookmark::bind($db, array(
			'belongsTo' => array('User', 'Item'),
		));
		
		Party::bind($db, array(
			'hasMany' => array(
				'User'=>array(
					'name' => 'users', 
					'column' => 'party_id', 
					'order' => 'age', 
				)
			), 
		));
		
		Image::bind($db, array(
		));
		
		Kiosk_finalize();
	}
	
	function assertSchemaFine() {
		$schema =& $this->data->schema('User');
		$this->assertIsA($schema, 'Kiosk_Schema');
		
		$this->assertEqual($schema->name, 'person');
		$this->assertEqual($schema->class, 'user');
	}
	
	function testCheckSchema1() {
		$this->env1();
		$this->assertSchemaFine();
	}
	
	function testBasicCRUD1() {
		$this->env1();
		
		$this->assertNull(User_load(1));
		$this->assertEqual(User_find(), array());
		
		// create
		
		$user = User_create();
		$this->assertIsA($user, 'User');
		
		$user->name = 'Taro';
		$user->save();
		
		$id = $user->id;
		
		$this->assertNotNull($id);
		$this->assertWithinMargin($user->created, time(), 2);
		
		// read
		
		$user2 = User_load($id);
		$this->assertEqual($user2->name, 'Taro');
		
		// update
		$user->name = 'Hanako';
		$user->save();
		
		$user2 = User_load($id);
		$this->assertEqual($user2->name, 'Hanako');
		
		// destroy
		$user->destroy();
		$this->assertNull($user->id);
		
		$this->assertNull(User_load($id));
		$this->assertEqual(User_find(), array());
	}
	
	function testMoreCreate() {
		$this->env1();
		
		// create
		
		$user1 = User_create(array('name'=>'Taro'));
		$this->assertEqual($user1->name, 'Taro');
		
		// non table column
		
		$user1->hello = 'World';
		$user1->save();
		
		// name alias
		
		$user1->is_admin = true;
		$user1->save();
		
		$table =& $this->db->table('person');
		$rows = $table->select(array(
			'columns' => array('id', 'admin_flag'), 
			'conditions' => "name LIKE 'Taro'", 
		), false);
		list($id, $flag, ) = array_first($rows);
		$this->assertTrue($flag);
		
		$user2 = User_load($user1->id);
		$this->assertTrue($user2->is_admin);
	}
	
	function testMoreUpdate() {
		$this->env1();
		
		// 準備
		
		list($user1, ) = User_import(array('name'=>'Taro'));
		$this->assertIsA($user1, 'User');
		
		// 参照先が保存されている場合の更新
		$user1->party = Party_load(1);
		$user1->save();
		
		$user2 = User_load($user1->id);
		$this->assertEqual($user2->party->id, 1);
	}
	
	function testNoPrimaryKeyCRUD() {
		$this->env1();
		
		$data = array('name' => 'hello', 'value' => 'world');
		
		// 準備
		Kiosk_reset();
		
		Preference::bind($this->db, array(
		));
		
		$pref = Preference_create($data);
		$this->assertTrue($pref->save());
		
		$pref = Preference_create($data);
		$this->assertTrue($pref->save());
		
		$this->assertEqual(count(Preference_find()), 2);
		
		// 削除
		$this->expectError();
		$pref->destroy();
	}
	
	function testSpecifiedPrimaryKeyCRUD() {
		$this->env1();
		
		$data = array('name' => 'hello', 'value' => 'world');
		
		// 明示的にキーを特定して準備
		Kiosk_reset();
		
		Preference::bind($this->db, array(
			'primaryKeys' => array('name')
		));
		
		$result = Preference::import(array($data, ));
		
		$pref = Preference_create($data);
		$this->assertEqual($result, array($pref));
		
		$pref->value = "world!";
		$this->assertTrue($pref->save());
		
		// loadも可能
		$pref2 = Preference::load(array('name' => 'hello'));
		$this->assertEqual($pref2->value, 'world!');
		
		// 存在しないキー
		$pref3 = Preference::load(array('name' => 'bye'));
		$this->assertNull($pref3);
		
		// 複数のロード
		$prefs = Preference::load(array(array('name' => 'hello')));
		$this->assertEqual($prefs[0]->value, 'world!');
		
		// 複数のロード（値のみ）主キーが一つの時のみ使用可能
		$prefs = Preference::load(array('hello'));
		$this->assertEqual($prefs[0]->value, 'world!');
		
		// 削除
		$pref->destroy();
		$pref4 = Preference::load(array('name' => 'hello'));
		$this->assertNull($pref4);
	}
	
	function testMultiplePrimaryKeysCRUD() {
		$this->env1();
		
		// 複数の主キーの準備
		Kiosk_reset();
		
		Preference::bind($this->db, array(
			'primaryKeys' => array('party', 'name'), 
			'refersTo' => array('Party' => array('load'=>true)), 
		));
		
		Party::bind($this->db, array(
		));
		
		$party = Party_load(1);
		
		// create
		
		$pk = array('party' => $party, 'name' => 'hello');
		
		Preference::import(array(
			$pk + array('value' => 'world'), 
			array('party' => $party, 'name' => 'foo', 'value' => 'bar'), 
		));
		
		$this->assertEqual(count(Preference::find()), 2);
		
		// read
		$pref2 = Preference::load($pk);
		$this->assertEqual($pref2->party, $party);
		
		// update
		$pref2->value = "world!";
		$this->assertTrue($pref2->save());
		
		$pref3 = Preference::load($pk);
		$this->assertEqual($pref3->value, 'world!');
		
		// ほかのものが更新されていないことを確認
		$this->assertEqual(count(Preference::find("value='world!'")), 1);
		
		// 削除
		$pref3->destroy();
		$pref4 = Preference::load($pk);
		$this->assertNull($pref4);
	}
	
	function testAnotherCreate() {
		// カラムの定義のないクラスでの場合でエラーになっていたので追加
		
		$this->env1();
		
		// create
		
		$user = User_create(array('name'=>'Taro'));
		$user->save();
		
		$item1 = Item_create(array('title'=>'Bianco', 'user' => $user));
		$this->assertEqual($item1->title, 'Bianco');
		
		// non table column
		
		$item1->hello = 'World';
		$result = $item1->save();
		$this->assertTrue($result);
		$this->assertNotNull($item1->id);
		
		$table =& $this->db->table('item');
		$rows = $table->select(array(
			'columns' => array('id', 'title'), 
			'conditions' => "id={$item1->id}", 
		));
		$this->assertEqual($rows[0]['title'], 'Bianco');
		
		$item2 = Item_load($item1->id);
		$this->assertEqual($item2->id, $item1->id);
	}
	
	function testCreateWithAssosiation1() {
		// すでに保存されているオブジェクトへの関連を持った場合をテストする
		$this->env1();
		
		$party = Party_find(array('conditions' => "tag='de'", 'first'));
		$this->assertIsA($party, 'Party');
		
		// create
		
		$user = User_create(array('name' => 'Taro', 'party' => $party));
		$user->save();
		
		$row = User_find(array(
				'raw', 
				'first', 
				'conditions' => array(
					'id' => $user->id
				)));
		$this->assertEqual($row['party_id'], $party->id);
		
		$user2 = User_load($user->id);
		$this->assertIsA($user2->party, 'Party');
		$this->assertEqual($user2->party->tag, 'de');
		
		$item = Item_create();
		$item->title = 'Foo';
		$item->user = $user;
		$item->save();
		$this->assertNotNull($item->id);
		
		$row = Item_find(array('raw', 'first', 
			'conditions' => array(
					'id' => $item->id
				)));
		$this->assertEqual($row['person_id'], $user->id);
		
		$item2 = Item_load($item->id);
		$item2->fetch('user');
		
		$user3 = $item2->user;
		$this->assertIsA($user3, 'User');
		$this->assertEqual($user3->party->tag, 'de');
	}
	
	function testCreateWithAssosiation2() {
		// まだ保存されていないオブジェクトへの関連を持った場合の保存をテストする
		$this->env1();
		
		// create
		
		$url = 'http://example.jp/path/to/image.png';
		
		$user = User_create(array('name' => 'Jiro'));
		$user->image = Image_create(array('url' => $url));
		$user->save();
		
		$this->assertNotNull($user->id);
		$this->assertNotNull($user->image->id);
		
		$row = Image_find(array('raw', 'first', 
			'conditions' => array(
					'id' => $user->image->id
				)));
		$this->assertEqual($row['url'], $url);
	}
	
	function testHasMany() {
		// hasManyのテスト
		$this->env1();
		
		// 準備
		
		$party = Party_find(array('conditions' => "tag='de'", 'first'));
		
		User_import(array(
			array('name' => 'Taro', 'age' => 40, 'party' => $party), 
			array('name' => 'Jiro', 'age' => 36), 
			array('name' => 'Saburo', 'age' => 33, 'party' => $party), 
		));
		
		$this->assertEqual(count(User_find()), 3);
		
		$users = $party->fetch('users');
		$this->assertTrue(is_array($users));
		$this->assertEqual(count($users), 2);
		$this->assertEqual($users[0]->name, 'Saburo');
	}
	
	function testMoreRead() {
		$this->env1();
		
		// create
		
		$user1 = User_create();
		$user1->name = 'Taro';
		$user1->save();
		
		$user2 = User_create();
		$user2->name = 'Hanako';
		$user2->save();
		
		$user3 = User_create();
		$user3->name = 'Yosuke';
		$user3->save();
		
		// read 1 : single load
		
		$user = User_load($user1->id);
		$this->assertEqual($user->name, 'Taro');
			
		// read 2 : multiple load
		$users = User_load(array($user1->id, $user2->id));
		$this->assertTrue(is_array($users));
		$this->assertEqual(count($users), 2);
		$this->assertEqual($users[$user1->id]->name, 'Taro');
		$this->assertEqual($users[$user2->id]->name, 'Hanako');
		
		// read 3 : find
		$users = User_find("name='Hanako'");
		$this->assertEqual(count($users), 1);
		$this->assertEqual($users[0]->name, 'Hanako');
		
		// read 4 : all
		$users = User_find();
		$this->assertEqual(count($users), 3);
	}
	
	function testMoreFind() {
		$this->env1();
		
		// create
		
		User::import(array(
			array('name' => 'Taro', 'no' => 35, ), 
			array('name' => 'Hanako', 'no' => 30, ), 
			array('name' => 'Yosuke', 'no' => 43, ), 
		));
		
		// 文字列
		$users = User_find("name='Hanako'");
		$this->assertEqual(count($users), 1);
		$this->assertEqual($users[0]->name, 'Hanako');
		
		// 単純な配列
		$users = User_find(array('conditions'=>array("name='Hanako'")));
		$this->assertEqual(count($users), 1);
		$this->assertEqual($users[0]->name, 'Hanako');
		
		// 単純なハッシュ
		$users = User_find(array('conditions'=>array('name' => 'Hanako')));
		$this->assertEqual(count($users), 1);
		$this->assertEqual($users[0]->name, 'Hanako');
		
		// 順番
		$users = User::find(array('order' => 'no'));
		$this->assertEqual($users[0]->name, 'Hanako');
		
		$users = User::find(array('order' => '-no'));
		$this->assertEqual($users[0]->name, 'Yosuke');
	}
	
	function testFetch() {
		$this->env1();
		
		User_import(array(
			array(
				'name' => 'Taro', 
				'age' => 40, 
				'col1' => 100, 
				'col3' => 200, 
				'profile' => 'Long long ago, in the galaxy far far away'
			), 
		));
		
		$user = User_find(array('first'));
		$this->assertEqual($user->name, 'Taro');
		$this->assertFalse(isset($user->profile));
		
		$user->fetch('profile');
		$this->assertFalse(empty($user->profile));
		
		$user->fetch(array('col1', 'col2', 'col3'));
		$this->assertEqual($user->col1, 100);
		$this->assertNull($user->col2);
		$this->assertEqual($user->col3, 200);
		
		// 存在しないカラム
// 		$this->expectError();
// 		$user->fetch('col4');
		
		// 存在しているカラムは、再度フェッチしてもかわらない
		$user->name = 'Jiro';
		$user->fetch('name');
		$this->assertEqual($user->name, 'Jiro');
		
	}
	
	function testFetchHasMany() {
		$this->env1();
		
		$user = User::create(array('name'=>'Taro'));
		$user->save();
		
		for ($i = 1; $i <= 100; $i++) {
			$item = Item::create();
			$item->title = sprintf('Title %d', $i);
			$item->user = $user;
			$item->save();
		}
		
		$items = $user->fetch('items');
		$this->assertEqual(count($items), 100);
		
		$items = $user->fetch('items', array('limit'=>10));
		$this->assertEqual(count($items), 10);
		
		$params = $user->paramsToFetch('items');
		$params['conditions'] = array(
			$params['conditions'], 
			'id >' => 10, 
			'id <=' => 20, 
		);
		$params['order'] = '-title';
		
		$items = $user->fetch('items', $params);
		$this->assertEqual(count($items), 10);
		$this->assertEqual($items[0]->title, 'Title 20');
	}
	
	function testHasOne() {
		$this->env1();
		
		Kiosk_reset();
		
		User::bind($this->db, array(
			'name' => 'person', 
			
			'columns' => array(
			),
			
			'hasOne' => array(
				'score' => array('class'=>'UserScore')
			)
		));
		
		UserScore::bind($this->db, array(
			'name' => 'person_result', 
			'belongsTo' => 'User',
		));
		
		User::import(array(
			array('name'=>'Taro'), 
			array('name'=>'Jiro'), 
			array('name'=>'Saburo'), // dummy
		));
		
		$users = User::find(array('order'=>'id'));
		$taro = $users[0];
		$jiro = $users[1];
		$this->assertEqual($taro->name, 'Taro');
		$this->assertEqual($jiro->name, 'Jiro');
		
		UserScore::import(array(
			array('user'=>$taro, 'score'=>200), 
			array('user'=>$jiro, 'score'=>100), 
		));
		
		// if hasOne column specified in "order", 
		// "inner join" will be used.
		
		$users = User::find(array('order'=>'score.score'));
		$this->assertEqual(count($users), 2);
		$this->assertEqual($users[0]->name, 'Jiro');
		
		$users = User::find(array('order'=>'-score.score'));
		$this->assertEqual(count($users), 2);
		$this->assertEqual($users[0]->name, 'Taro');
		
		// if hasOne column specified in 'columns', 
		// "inner join" will be used.
		
		$users = User::find(array(
			'columns' => array(
				'id', 
				'name',
				'score.score',
			), 
			'order' => 'name', 
		));
		$this->assertEqual(count($users), 2);
		$this->assertEqual($users[0]->name, 'Jiro');
		$this->assertEqual($users[0]->score->score, 100);
	}
}

