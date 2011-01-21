<?php

require_once dirname(__FILE__). '/shared/db.php';

function sqlite_config() {
	$config = array();
	
	$config['db'] = ':memory:';
	
	return $config;
}

class Kiosk_Data_SQLiteSourceTestCase extends CommonDBTestCase {
	function setUp() {
		$this->db = Kiosk::database('sqlite', sqlite_config());
		
		$this->db->exec("
			CREATE TABLE ". TESTDB. "(
				id INTEGER PRIMARY KEY, 
				name TEXT, 
				value REAL, 
				age INTEGER,
				created TIMESTAMP DEFAULT 'CURRENT_TIMESTAMP'
			)");
		
		parent::setUp();
	}
	
	function tearDown() {
		$this->db->exec("DROP TABLE ". TESTDB);
	}
	
	function testSchema() {
		$this->schema_test();
	}
	
	function testFetch() {
		$this->fetch_test();
	}
	
	function testTableObject_selectAndLoad() {
		$this->tableObject_selectAndLoad_test();
	}
	
	function testTableObject_Insert() {
		$this->tableObject_Insert_test();
	}
	
	function testTableObject_Update() {
		$this->tableObject_update_test();
	}
}

