<?php

require_once dirname(__FILE__). '/shared/db.php';

function pgsql_config() {
	$config = array();
	
	$config['host'] = 'rose';
	$config['user'] = 'apache';
	$config['password'] = '';
	$config['db'] = "kanshin_beta";
	
	return $config;
}

class Kiosk_Data_PGSQLSourceTestCase extends CommonDBTestCase {
    function skip() {
    	$this->skipUnless(file_explicitly_specified(basename(__FILE__)));
    }
    
	function setUp() {
		$this->db = Kiosk::database('pgsql', pgsql_config());
		
		@$this->db->exec("DROP TABLE ". TESTDB);
		
		$this->db->exec("
			CREATE TABLE ". TESTDB. "(
				id serial primary key, 
				name text, 
				value float, 
				age int,
				created timestamp with time zone default now()
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
	
	function testColumnTypeConversion() {
		$this->columnType_test();
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
	
	function testPgSchema() {
		$info = $this->table->describe();
		$this->assertEqual($info['id']['sequence'], 'unittestdb_id_seq');
		
		$id = $this->db->nextId($this->table->name);
		$this->assertEqual($id, 1);
		
		$id = $this->db->lastId($this->table->name);
		$this->assertEqual($id, 1);
		
		$id = $this->db->nextId($this->table->name);
		$this->assertEqual($id, 2);
	}
}

