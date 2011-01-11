<?php

require_once KIOSK_LIB_DIR. '/data/object/CRUD.php';
require_once KIOSK_LIB_DIR. '/data/sources/DB.php';

if (KIOSK_HAS_REAL_CLASS) {
	require_once KIOSK_LIB_DIR. '/data/object/crud/Concrete_5_3.php';
} else if (KIOSK_PHP_4) {
	require_once KIOSK_LIB_DIR. '/data/object/crud/Concrete_4.php';
} else {
	require_once KIOSK_LIB_DIR. '/data/object/crud/Concrete_5.php';
}

class KioskObject extends Kiosk_Object_CRUD_Concrete {
	/*
		データベースオブジェクトを生成して返す
	*/
	function database($driver, $config=array()) {
		if (is_array($driver)) {
			$config = $driver;
		} else {
			$config['driver'] = $driver;
		}
		
		$config['type'] = 'DB';
		
		$data =& Kiosk_data();
		return $data->source(null, $config);
	}
}

