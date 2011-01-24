<?php

function _require_kiosk_test($path) {
	foreach (glob($path) as $path) {
		if (is_dir($path)) {
			_require_kiosk_test($path. '/*');
		} else if (file_exists($path)) {
			require_once $path;
		}
	}
}

_require_kiosk_test(dirname(__FILE__). '/data/*');

