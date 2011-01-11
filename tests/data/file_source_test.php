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
	
	function testFileSourceBind() {
		$fs = Kiosk::source('file', array(
			'type' => 'File', 
		));
		
		$this->assertIsA($fs, 'Kiosk_Data_Source');
		
		MockFileEntity::bind($fs, array(
			'source' => 'File', 
			'path' => $this->tmp_path, 
		));
	}
}

