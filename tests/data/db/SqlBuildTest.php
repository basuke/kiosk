<?php

require_once 'kiosk/data/sources/db/SQL.php';

class Kiosk_Data_DBSqlTestCase extends UnitTestCase {
	function testLiterals() {
		$lang = new Kiosk_Data_DB_SQL();
		
		$this->assertEqual('NULL', $lang->literal(null));
		$this->assertEqual(0, $lang->literal(0));
		$this->assertEqual(2, $lang->literal('2'));
		$this->assertEqual(3.0, $lang->literal(3.0));
		$this->assertEqual('TRUE', $lang->literal(true));
		$this->assertEqual('FALSE', $lang->literal(false));
		$this->assertEqual("'abc'", $lang->literal('abc'));
		$this->assertEqual("'Rock\\'n Roll'", $lang->literal("Rock'n Roll"));
	}
	
	function testInsert() {
		$table = 'hello';
		$columns = array('col1' => 1, 'col2' => 'foo', 'col3' => null);
		
		$lang = new Kiosk_Data_DB_SQL();
		
		$this->assertEqual(
			"INSERT INTO \"hello\"(col1,col2,col3) VALUES (1,'foo',NULL)", 
			$lang->insertStatement($table, $columns)
		);
	}
	
	function testUpdate() {
		$table = 'hello';
		$columns = array('col1' => 1, 'col2' => 'foo', 'col3=max(id)+1');
		$conditions = 'col4 <> 100';
		
		$lang = new Kiosk_Data_DB_SQL();
		
		$this->assertEqual(
			"UPDATE \"hello\" SET col1=1,col2='foo',col3=max(id)+1 WHERE col4 <> 100", 
			$lang->updateStatement($table, $columns, $conditions)
		);
	}
}

