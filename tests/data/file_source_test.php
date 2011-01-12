<?php

/*
	Kiosk test suite.
	
	@objective
		- file data source works correctly.
		- multi source dispatch works fine.
		- basic classes have correct definitions.
*/

require_once 'Kiosk.php';

class MockFileEntity extends Kiosk {
}

class Kiosk_Data_FileSource_TestCase extends UnitTestCase {
	var $tmp_path;
	
	function setUp() {
		Kiosk_reset();
		
		$this->tmp_path = tempnam('/tmp', 'kiosk-data-test');
	}
	
	function tearDown() {
		@unlink($this->tmp_path);
		$this->tmp_path = null;
		
		Kiosk_reset();
	}
	
	function testBasicCRUD() {
		$fs = Kiosk::source('file', array(
			'type' => 'File', 
		));
		
		$this->assertIsA($fs, 'Kiosk_Data_Source');
		
		MockFileEntity::bind($fs, array(
			'path' => $this->tmp_path, 
			'columns' => array('col1', 'col2', 'col3'), 
		));
		
		// create
		
		$e = MockFileEntity::create();
		$this->assertIsA($e, 'MockFileEntity');
		
		$e->col1 = 'Hello';
		$e->col2 = 'Help';
		$e->col3 = 12345;
		$this->assertTrue($e->save());
		
		$contents = file_get_contents($this->tmp_path);
		$this->assertEqual($contents, "Hello,Help,12345\n");
		
		// read
		
		$items = MockFileEntity::find();
		$this->assertTrue(is_array($items));
		$this->assertEqual(count($items), 1);
		$this->assertIsA($items[0], 'MockFileEntity');
		$this->assertEqual($items[0]->col1, 'Hello');
	}
}

