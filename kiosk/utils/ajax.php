<?php

require_once 'JSON.php';
require_once 'kiosk/utils/http.php';

$json = new Services_JSON();

function to_json($value) {
	global $json;
	return $json->encode($value);
}

function json_response($value) {
	set_content_type(DEVELOPMENT ? 'text/plain' : 'application/json');
	
	echo to_json($value);
}

function jsonp_response($value, $callback) {
	set_content_type('text/javascript');
	echo $callback, '(', to_json($value), ');', "\n";
}

