<?php

/*
	Base class for data source handler.
*/

class Kiosk_Data_Source {
	/*
		Open source with specified configuration, and return instance.
		
		@param $config Array configration hash.
		@returns source instance.
		
		@access static public
	*/
	function &openSource($config) {
		return null;
	}
}

