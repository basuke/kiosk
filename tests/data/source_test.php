<?php

require_once 'Kiosk.php';

class Kiosk_Data_Source_TestCase extends UnitTestCase {
	function testBasic() {
		Kiosk_reset();
		
		Kiosk::source('default', 'sqlite');
		
		$source =& Kiosk::source('default');
		$this->assertIsA($source, 'Kiosk_DB_Driver');
	}
}

