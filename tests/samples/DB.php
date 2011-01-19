<?php

require_once KIOSK_LIB_DIR. '/utils/console.php';

function &open_test_database() {
	$db =& Kiosk::database('sqlite');
	
	if (defined('SHOWSQL')) {
		require_once KIOSK_LIB_DIR. '/Logger.php';
		$db->setLogger(new Kiosk_Logger());
	}
	
	return $db;
}

function close_test_database(&$db) {
	if ($db) {
		if (defined('DUMPDB')) {
			dump_database($db);
		}
		
		$db->disconnect();
	}
}

function sample_database_schemaq(&$db) {
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
			person_id INTEGER,
			title TEXT, 
			description TEXT, 
			
			col1 INTEGER, 
			col2 INTEGER, 
			col3 INTEGER, 
			
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
}

function dump_database($db) {
	echo "DUMP BEGIN ====================\n";
	
	foreach ($db->dump($db->tables()) as $name=>$rows) {
		console_out(array(
			'heading3' => "TABLE {$name}", 
			"\n"
		));
		
		foreach ($rows as $index=>$row) {
			echo "#{$index} ";
			foreach ($row as $key=>$value) {
				console_out(array(
					$key, ': ', 
					'green' => $db->literal($value), 
					' ',
				));
			}
			
			echo "\n";
		}
	}
	
	echo "DUMP END ======================\n";
}

