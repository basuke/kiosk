<?php

class Kiosk_App_JSONRenderer {
	function render(&$app, &$context) {
		header('Content-type: application/json');
		
		echo json_encode($context->controllerResult());
	}
}

