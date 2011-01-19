<?php

/*
	Kiosk test suite.
	
	@objective
		- multi source dispatch works fine.
		- basic classes have correct definitions.
*/

require_once 'Kiosk.php';

class Kiosk_Data_Source_TestCase extends UnitTestCase {
	function testBasic() {
		Kiosk_reset();
		
		Kiosk::source('default', array(
			'type' => 'DB', 
			'driver' => 'sqlite'
		));
		
		$source =& Kiosk::source('default');
		$this->assertIsA($source, 'Kiosk_Data_Source_DB');
		
		Kiosk::source('another', array(
			'type' => 'file', 
		));
		
		$fs = Kiosk::source('another');
		$this->assertIsA($fs, 'Kiosk_Data_Source_File');
		
		$null = Kiosk::source('null', array(
			'type' => 'null', 
		));
		
		$this->assertIsA($null, 'Kiosk_Data_Source_Null');
	}
}

