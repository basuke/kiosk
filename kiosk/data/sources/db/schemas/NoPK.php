<?php

class Kiosk_Schema_DB_NoPrimaryKeys extends Kiosk_Schema {
	function fetchColumn(&$obj, $name, $params) {
		return trigger_error(KIOSK_ERROR_CONFIG. 'cannot fetch column without primary key');
	}
}

