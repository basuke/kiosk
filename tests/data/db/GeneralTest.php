<?php

require_once 'Kiosk.php';

class Kiosk_Data_DBGeneralTestCase extends UnitTestCase {
	function testFactory() {
		$db = Kiosk::database('sqlite');
		$this->assertIsA($db, 'Kiosk_Data_Source_DB_SQLite');
	}
	
	function testEmptyDriverError() {
		// 'driver'は必須で空であってはいけない
		$this->expectError();
		$db = Kiosk::database('', array());
	}
	
	function testEmptyDriverInConfigError() {
		$this->expectError();
		// 配列で渡す場合、'driver'は必須
		$db = Kiosk::database(array());
	}
	
	function &memberTable(&$db) {
		$db->exec("CREATE TABLE members (id integer primary key, name TEXT, age INT)");
		$db->exec("INSERT INTO members(name,age) VALUES('Taro', 42)");
		$db->exec("INSERT INTO members(name,age) VALUES('Hanako', 23)");
		$db->exec("INSERT INTO members(name,age) VALUES('Kinoko', 35)");
		$db->exec("INSERT INTO members(name,age) VALUES('Yasuko', 20)");
		$db->exec("INSERT INTO members(name,age) VALUES('Keiko', 38)");
		$db->exec("INSERT INTO members(name,age) VALUES('Yukio', 46)");
		$db->exec("INSERT INTO members(name,age) VALUES('Noboru', 46)");
		
		return $db->table('members');
	}
	
	/* memberにbelongsToであるitemsテーブル */
	function &itemtable(&$db) {
		$db->exec("CREATE TABLE items (id integer primary key, name TEXT, member_id INT)");
		$db->exec("INSERT INTO items(name,member_id) VALUES('Mac', 1)");
		$db->exec("INSERT INTO items(name,member_id) VALUES('iPhone', 2)");
		$db->exec("INSERT INTO items(name,member_id) VALUES('Windows', 3)");
		
		return $db->table('items');
	}
	
	/* memberにbelongsToであるtagテーブル */
	function &tagTable(&$db) {
		$db->exec("CREATE TABLE tags (id integer primary key, tag TEXT, member_id INT)");
		$db->exec("INSERT INTO tags(tag,member_id) VALUES('computer', 1)");
		$db->exec("INSERT INTO tags(tag,member_id) VALUES('hobby', 1)");
		$db->exec("INSERT INTO tags(tag,member_id) VALUES('work', 2)");
		
		return $db->table('tags');
	}
	
	function testSelect() {
		$db =& Kiosk::database('sqlite');
		$table =& $this->memberTable($db);
		
		$row = array_first($table->select('age=23'));
		$this->assertEqual($row['name'], 'Hanako');
		$this->assertEqual($row['age'], 23);
		
		$row = array_first($table->select('age=35', false));
		$this->assertEqual($row[1], 'Kinoko');
		$this->assertEqual($row[2], 35);
		
		$row = array_first($table->select('age=55'));
		$this->assertNull($row);
		
		$rows = $table->select(array('order'=>'age'));
		$this->assertEqual($rows[2]['name'], 'Kinoko');
		
		$rows = $table->select(array('order'=>'age DESC, name'));
		$this->assertEqual($rows[2]['name'], 'Taro');
		$this->assertEqual($rows[0]['name'], 'Noboru');
		
		$rows = $table->select(array('order'=>'name', 'limit'=>3, 'offset'=>2));
		$this->assertEqual(count($rows), 3);
		$this->assertEqual($rows[0]['name'], 'Kinoko');
	}
	
	function testSelectJoin() {
		$db =& Kiosk::database('sqlite');
		$members =& $this->memberTable($db);
		$items =& $this->itemTable($db);
		$tags =& $this->tagTable($db);
		
		$params = array(
			'conditions' => '"items".name = \'iPhone\'', 
			'columns' => 'items.id, items.name, items.member_id, '.
						 'members.id, members.name, members.age', 
		);
		
		$rows = $items->select(array(
			'join' => array(
				'table' => 'members', 
				'on' => array("items.member_id"=>"members.id")
			)
		) + $params);
		
		$this->assertEqual(count($rows), 1);
		$this->assertEqual($rows[0]['members.name'], 'Hanako');
		
		// array of join
		$rows = $items->select(array(
			'join' => array(
				array(
					'table' => 'members', 
					'on' => "items.member_id=members.id",
				)
			)
		) + $params);
		
		$this->assertEqual(count($rows), 1);
		$this->assertEqual($rows[0]['members.name'], 'Hanako');
		
		// alias
		$rows = $items->select(array(
			'columns' => 'items.id, items.name, items.member_id, '.
						 't1.id, t1.name, t1.age', 
			'join' => array(
				'table' => 'members', 
				'alias' => 't1', 
				'on' => array("items.member_id"=>"t1.id")
			)
		) + $params);
		
		$this->assertEqual(count($rows), 1);
		$this->assertEqual($rows[0]['t1.name'], 'Hanako');
		
		// multi join
		$p = array(
			'columns' => 'tags.id, tags.tag, i.name, m.id, m.name',
			'conditions' => '"i".name = \'Mac\'', 
			'join' => array(
				array(
					'table' => 'members', 
					'alias' => 'm', 
					'on' => "tags.member_id=m.id",
				), 
				array(
					'table' => 'items', 
					'alias' => 'i', 
					'on' => "i.member_id=m.id",
				)
			)
		) + $params;
		
		$rows = $tags->select($p);
		$this->assertEqual(count($rows), 2);
		$this->assertEqual($rows[0]['m.name'], 'Taro');
		
	}
}

