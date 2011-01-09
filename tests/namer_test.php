<?php

require_once 'Kiosk.php';

class Kiosk_Namer_TestCase extends UnitTestCase {
	function testPluralize() {
		$namer = new Kiosk_Namer();
		
		$cases = array(
			'book' => 'books', 
			'file' => 'files', 
			'Keyword' => 'Keywords', 
			'My Revolution' => 'My Revolutions', 
		);
		
		foreach ($cases as $word => $expects) {
			$this->assertEqual($namer->pluralize($word), $expects, "$expects: %s");
		}
	}
	
	function testSingularize() {
		$namer = new Kiosk_Namer();
		
		$cases = array(
			'books' => 'book', 
			'files' => 'file', 
			'Keywords' => 'Keyword', 
			'My Revolutions' => 'My Revolution', 
		);
		
		foreach ($cases as $word => $expects) {
			$this->assertEqual($namer->singularize($word), $expects, "$expects: %s");
		}
	}
}

