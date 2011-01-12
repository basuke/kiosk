<?php

/*
	Base class for data source handler.
*/

require_once KIOSK_LIB_DIR. '/data/Query.php';
require_once KIOSK_LIB_DIR. '/data/Schema.php';
require_once KIOSK_LIB_DIR. '/data/Association.php';

class Kiosk_Data_Source {
	/*
		Open source with specified configuration, and return instance.
		
		@param $config Array configration hash.
		@returns source instance.
		
		@access static public
	*/
	function &open($config) {
		return null;
	}
	
	var $logger;
	
	function Kiosk_Data_Source($config=array()) {
		$this->__construct($config);
	}
	
	function __construct($config=array()) {
	}
	
	// log
	
	function setLogger(&$logger) {
		$this->logger =& $logger;
	}
	
	function log($priority, $msg) {
		if (! $this->logger) return;
		
		$this->logger->log($priority, $msg);
	}
	
	// schema creation
	
	function &buildSchema($class, $params) {
		return new Kiosk_Data_Schema($class, $this, $params);
	}
}

