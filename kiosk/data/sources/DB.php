<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';

class Kiosk_Data_Source_DB extends Kiosk_Data_Source {
	/* static */
	function &openSource($config) {
		if (is_string($config)) {
			$driver = $config;
			$config = array();
		} else {
			$driver = $config['driver'];
			unset($config['driver']);
		}
		
		if (!$driver) {
			return trigger_error(KIOSK_ERROR_CONFIG. 'no source driver specified');
		}
		
		$class = Kiosk_Data_Source_DB::_findAndLoadDriverClass($driver);
		if (!$class) {
			return trigger_error(KIOSK_ERROR_CONFIG. "no source driver found: {$driver}");
		}
		
		return new $class($config);
	}
	
	/* static private */
	function _findAndLoadDriverClass($driver) {
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
}

