#!/bin/env php
<?php

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

require_once dirname(__FILE__). '/test-config.php';

if (! defined('SIMPLETEST')) {
	define('SIMPLETEST', '../simpletest-1.0.1');
}

require_once SIMPLETEST. '/autorun.php';

class AllTests extends TestSuite {
	function AllTests() {
		$this->TestSuite('tests');
		
		global $args;
		$files = ($args ? $args : glob(dirname(__FILE__). '/tests/*'));
		
		while ($files) {
			$file = array_shift($files);
			
			if (is_dir($file)) {
			} else if (file_exists($file)) {
				$this->addFile($file);
			}
		}
	}
}

class ConsoleReporter extends TextReporter {
    function paintFooter($test_name) {
    	$fail = $this->getFailCount();
    	$exception = $this->getExceptionCount();
    	
        if ($fail + $exception == 0) {
            console_out(array('success'=>'OK', "\n"));
        } else {
            console_out(array('error'=>'FAIL', "\n"));
        }
        
        console_out(array(
        	array('option' => "Test cases run: "), 
        		$this->getTestCaseProgress(). "/" . $this->getTestCaseCount(), 
        		', ',
        	array('option' => "Passes: "), 
        		$this->getPassCount(), 
        		', ',
        	array('option' => "Failures: "), 
        		array(($fail ? 'error' : 0) => $fail), 
        		', ',
        	array('option' => 'Exceptions: '), 
        		array(($exception ? 'error' : 0) => $exception), 
        	"\n"
        ));
    }
    
    function paintSkip($message) {
    	if (! defined('SHOWSKIP')) return;
    	
        parent::paintSkip($message);
    }
}

SimpleTest::prefer(new ConsoleReporter());

function file_explicitly_specified($file) {
	global $args;
	foreach ($args as $path) {
		if (strpos($path, $file) !== false) return true;
	}
	return false;
}

require_once dirname(__FILE__). '/Kiosk.php';
require_once dirname(__FILE__). '/kiosk/utils/console.php';

function &open_test_database() {
	$db =& Kiosk::database('sqlite');
	
	if (defined('SHOWSQL')) {
		require_once dirname(__FILE__). '/kiosk/Logger.php';
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

