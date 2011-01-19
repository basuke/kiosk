<?php

class Kiosk_Test_Test {
	var $_simpletest_path = null;
	
	function setupSimpletest($path) {
		$this->_simpletest_path = $path;
		
		require_once $this->_simpletest_path. '/autorun.php';
		require_once $this->_simpletest_path. '/web_tester.php';
		require_once KIOSK_LIB_DIR. '/test/TextReporter.php';
	}
}

