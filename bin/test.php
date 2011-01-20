<?php

require_once dirname(dirname(__FILE__)). '/Kiosk.php';

require_once 'Console/Getopt.php';

$opt  = new Console_Getopt();
list($options, $args) = $opt->getopt(
	$opt->readPHPArgv(), 
	't:',
	array('simpletest=', 'showsql', 'dumpdb')
);

foreach ($options as $option) {
	list($name, $value) = $option;
	
	switch ($name) {
		case 't':
			break;
			
		default:
			$name = strtoupper(str_replace('--', '', $name));
			if (! defined($name)) {
				define($name, $value);
			}
			break;
	}
}

require_once dirname(__FILE__). '/config.php';

if (! defined('SIMPLETEST')) {
	define('SIMPLETEST', '../simpletest-1.0.1');
}
require_once SIMPLETEST. '/autorun.php';
require_once KIOSK_LIB_DIR. '/test/TextReporter.php';

class AllTests extends TestSuite {
	function AllTests() {
		$this->TestSuite('tests');
		
		global $args;
		$files = ($args ? $args : glob(KIOSK_HOME. '/tests/*'));
		
		while ($files) {
			$file = array_shift($files);
			
			if (is_dir($file)) {
			} else if (file_exists($file)) {
				$this->addFile($file);
			}
		}
	}
}

function file_explicitly_specified($file) {
	global $args;
	foreach ($args as $path) {
		if (strpos($path, $file) !== false) return true;
	}
	return false;
}


