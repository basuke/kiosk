<?php

class Kiosk_Logger {
	var $minimum_priority = LOG_NOTICE;
	
	function log($priority, $msg) {
		echo $msg, "\n";
	}
	
	function err($msg) {
		$this->log(LOG_ERR, $msg);
	}
	
	function warn($msg) {
		$this->log(LOG_WARNING, $msg);
	}
	
	function info($msg) {
		$this->log(LOG_INFO, $msg);
	}
	
	function debug($msg) {
		$this->log(LOG_DEBUG, $msg);
	}
}

