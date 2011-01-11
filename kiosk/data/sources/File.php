<?php

require_once KIOSK_LIB_DIR. '/data/Source.php';

class Kiosk_Data_Source_File extends Kiosk_Data_Source {
	/* static */
	function &open($config) {
		return new Kiosk_Data_Source_File($config);
	}
	
}

