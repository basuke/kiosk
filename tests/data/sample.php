<?php

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

function sample_schema1(&$db) {
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

