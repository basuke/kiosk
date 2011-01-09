<?php

require_once 'Kiosk.php';

class Kiosk_Magic_Mock extends Kiosk {
}

class Kiosk_Magic_TestCase extends UnitTestCase {
	function testBasic() {
		$db = Kiosk::database('sqlite');
		
		Kiosk_reset();
		
		Kiosk_bind('Kiosk_Magic_Mock', $db);
		
		$obj = Kiosk_Magic_Mock::create();
		$this->assertIsA($obj, 'Kiosk_Magic_Mock');
		
		$this->assertTrue(function_exists('Kiosk_Magic_Mock_create'));
		
		$obj2 = Kiosk_Magic_Mock_create();
		$this->assertIsA($obj2, 'Kiosk_Magic_Mock');
	}
}

