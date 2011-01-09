<?php

class Kiosk_Logger {
	var $minimum_priority = LOG_NOTICE;
	
	function log($priority, $msg) {
		echo $msg, "\n";
	}
}

