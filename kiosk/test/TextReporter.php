<?php

/**
 *	SimpleTestのTextReporterを拡張してカラー表示に。
 *	simpletest/reporter.php が読み込まれている必要がある。
 */

require_once KIOSK_LIB_DIR. '/utils/console.php';

class Kiosk_Test_TextReporter extends TextReporter {
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

SimpleTest::prefer(new Kiosk_Test_TextReporter());

