<?php

require_once dirname(__FILE__). '/shared/db.php';

function mysql_config() {
	$config = array();
	
	$config['host'] = 'com-mysql-server';
	$config['user'] = 'campaign';
	$config['password'] = 'campaign-a-go-go';
	$config['db'] = "campaign_dev";
	
	return $config;
}

class Kiosk_Data_MySQLDBConnectionTestCase extends UnitTestCase {
    function skip() {
    	$this->skipUnless(file_explicitly_specified(basename(__FILE__)));
    }
    
	function testConnection() {
		// 開発環境であることを確認
		$this->assertTrue(DEVELOPMENT);
		
		$db = Kiosk::database('mysql', mysql_config());
		
		// 接続されていることを確認
		$this->assertNotNull($db->conn);
	}
	
	
}

class Kiosk_Data_MySQLSourceTestCase extends CommonDBTestCase {
    function skip() {
    	$this->skipUnless(file_explicitly_specified(basename(__FILE__)));
    }
    
	function setUp() {
		$this->db = Kiosk::database('mysql', mysql_config());
		
		$this->db->exec("DROP TABLE IF EXISTS ". TESTDB);
		
		$this->db->exec("
			CREATE TABLE ". TESTDB. "(
				id int auto_increment, 
				name text, 
				value float, 
				age int,
				created timestamp, 
				
				primary key(id)
			)");
		
		parent::setUp();
	}
	
	function tearDown() {
		$this->db->exec("DROP TABLE IF EXISTS ". TESTDB);
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
		$this->tableObject_update_test(2);
	}
}

