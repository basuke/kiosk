<?php

function Kiosk_DB_factory($driver, $config) {
	$class = Kiosk_DB_findAndLoadDriverClass($driver);
	if (!$class) {
		return null;
	}
	
	return new $class($config);
}

function Kiosk_DB_findAndLoadDriverClass($driver) {
	$dir = dirname(__FILE__);
	$pattern = '|/'. strtolower($driver). '\\.php$|';
	
	foreach (glob("$dir/db/drivers/*.php") as $path) {
		if (preg_match($pattern, strtolower($path))) {
			require_once $path;
			return 'Kiosk_DB_Driver_'. $driver;
		}
	}
	
	return null;
}

