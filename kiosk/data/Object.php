<?php

require_once dirname(__FILE__). '/object/CRUD.php';
require_once dirname(__FILE__). '/sources/DB.php';

if (KIOSK_HAS_REAL_CLASS) {
	require_once dirname(__FILE__). '/object/crud/Concrete_php5.php';
} else if (KIOSK_PHP_4) {
	require_once dirname(__FILE__). '/object/crud/Concrete_php4.php';
} else {
	trigger_error('Kiosk requires PHP 5.3 or later');
}

class KioskObject extends Kiosk_Object_CRUD_Concrete {
	/*
		データベースオブジェクトを生成して返す
	*/
	function database($driver, $config=array()) {
		if (is_array($driver)) {
			$config = $driver;
			
			if (isset($config['driver'])) {
				$driver = $config['driver'];
				unset($config['driver']);
			}
		}
		
		if (!$driver) {
			return trigger_error(KIOSK_ERROR_CONFIG. 'no database driver specified');
		}
		
		return Kiosk_DB_factory($driver, $config);
	}
}

