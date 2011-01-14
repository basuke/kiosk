<?php

define('TESTDB', 'UnitTestDB');

require_once 'Kiosk.php';

class CommonDBTestCase extends UnitTestCase {
	// setupで$this->dbを定義し、 tearDownで掃除する
	
	function setUp() {
		$this->table = $this->db->table(TESTDB);
		
		if (defined('SHOWSQL')) {
			require_once 'kiosk/Logger.php';
			$this->db->setLogger(new Kiosk_Logger());
		}
	}
	
	function schema_test() {
		$table =& $this->table;
		
		$schema = $table->describe();
		
		$this->assertEqual('integer', $schema['id']['type']);
		$this->assertEqual('string', $schema['name']['type']);
		$this->assertEqual('float', $schema['value']['type']);
		$this->assertEqual('integer', $schema['age']['type']);
		$this->assertEqual('timestamp', $schema['created']['type']);
		
		$this->assertTrue($schema['id']['primaryKey']);
		$this->assertFalse($schema['name']['primaryKey']);
		
		$this->assertEqual('id', $schema['id']['name']);
		
		$tables = $this->db->tables();
		$this->assertTrue(is_array($tables));
		$this->assertTrue(in_array(TESTDB, $tables) or in_array(strtolower(TESTDB), $tables));
	}
	
	function fetch_test() {
		$db = $this->db;
		
		// ====== No data
		
		$rows = $db->fetchRows('SELECT * FROM '. TESTDB);
		
		$this->assertNotNull($rows);
		$this->assertTrue(is_array($rows));
		$this->assertEqual(0, count($rows));
		
		$this->assertEqual(0, $db->count('SELECT id FROM '. TESTDB));
		
		// ====== INSERT 1 row
		
		$result = $db->exec("INSERT INTO ". TESTDB. "(name, value, age) values('Hello', 3.14, 23)");
		
		$this->assertEqual(1, $result);
		
		// ====== Fetch again
		
		$rows = $db->fetchRows('SELECT * FROM '. TESTDB);
		
		$this->assertEqual(1, count($rows));
		
		$this->assertEqual(1, $db->count('SELECT id FROM '. TESTDB));
		
		$row = $db->fetchOne('SELECT * FROM '. TESTDB);
		
		$this->assertEqual($row, $rows[0]);
	}
	
	function columnType_test() {
		$db = $this->db;
		
		// ====== INSERT 1 row
		
		$db->exec("INSERT INTO ". TESTDB. "(name, value, age) values('Hello', 3.14, 23)");
		
		$row = $db->fetchOne('SELECT * FROM '. TESTDB);
		
		// 型が変換されていることを確認する
		$this->assertTrue(is_string($row['name']));
		$this->assertTrue(is_float($row['value']));
		$this->assertTrue(is_integer($row['age']));
		$this->assertTrue(is_integer($row['created']));
		
		// タイムスタンプ型の値がおおむね正しいことを確認
		$this->assertTrue(abs($row['created'] - time()) < 2, "Maybe fail if db is busy.");
	}
	
	function tableObject_selectAndLoad_test() {
		$table = $this->table;
		$this->assertNotNull($table);
		
		// ====== No data
		
		$rows = $table->select(array());
		
		$this->assertNotNull($rows);
		$this->assertTrue(is_array($rows));
		$this->assertEqual(0, count($rows));
		
		// ====== INSERT 3 row
		
		$this->db->exec("INSERT INTO ". TESTDB. "(name, value, age) values('Hello', 3.14, 23)");
		$this->db->exec("INSERT INTO ". TESTDB. "(name, value, age) values('World', 1.41, 34)");
		$this->db->exec("INSERT INTO ". TESTDB. "(name, value, age) values('Again', 2.72, 45)");
		
		// ====== Fetch again
		
		$rows = $table->select(array());
		
		$this->assertEqual(3, count($rows));
		
		// ====== execute query
		
		$rows = $table->select(array('conditions'=>"name LIKE 'World'"));
		
		$this->assertEqual(1, count($rows));
		$this->assertEqual(34, $rows[0]['age']);
		
		// ====== 引き続き、ロードのテスト
		
		$id = $rows[0]['id']; // 今読み込んだ行のIDを取得
		$rows = $table->select('id='. $id);
		
		$this->assertEqual($rows[0]['age'], 34);
		
		$rows = $table->select("id in ($id)");
		
		$this->assertEqual(count($rows), 1);
		$this->assertEqual($rows[0]['age'], 34);
	}
	
	function tableObject_Insert_test() {
		$table = $this->table;
		
		$this->assertEqual(0, $table->count());
		
		$count = $table->insert(array(
			'name' => 'Hanako Yamada', 
			'value' => 100.1, 
			'age' => 32, 
		));
		
		$this->assertEqual(1, $count);
		$this->assertEqual(1, $table->count());
		
		$id1 = $table->lastId();
		$this->assertNotNull($id1);
		
		$count = $table->insert(array(
			'name' => 'Hiroto Komoto', 
			'value' => null, 
			'age' => 12, 
		));
		
		$this->assertEqual(1, $count);
		$this->assertEqual(2, $table->count());
		
		$count = $table->insert(array(
			'name' => 'Taro Kaneda', 
			'value' => 200, 
			'age' => 53, 
		));
		
		$this->assertEqual(1, $count);
		$this->assertEqual(3, $table->count());
		
		$rows = $table->select(array('conditions'=>'age=12'));
		
		$this->assertEqual(1, count($rows));
		$this->assertEqual('Hiroto Komoto', $rows[0]['name']);
		$this->assertNull($rows[0]['value']);
		
		$rows = $table->select("id=$id1");
		$this->assertEqual('Hanako Yamada', $rows[0]['name']);
	}
	
	function tableObject_update_test($expected_count=3) {
		$table = $this->table;
		
		$table->insert(array(
			'name' => 'Hanako Yamada', 
			'value' => 100.1, 
			'age' => 32, 
		));
		
		$table->insert(array(
			'name' => 'Hiroto Komoto', 
			'value' => null, 
			'age' => 12, 
		));
		
		$table->insert(array(
			'name' => 'Taro Kaneda', 
			'value' => 200, 
			'age' => 53, 
		));
		
		$this->assertEqual(3, $table->count());
		
		$count = $table->update(array('age' => 42), 'age=12');
		
		$this->assertEqual(1, $count);
		$this->assertEqual(3, $table->count());
		
		$count = $table->update(array('age' => 42), 'age=12');
		
		// 該当する人はいないので何も更新されない
		$this->assertEqual(0, $count);
		
		$count = $table->update(array('value' => null), 'age<100');
		
		// 全員該当する
		$this->assertEqual($expected_count, $count);
		
	}
	
}

