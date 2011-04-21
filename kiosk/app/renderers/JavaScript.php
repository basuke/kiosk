<?php

require_once KIOSK_LIB_DIR. '/app/renderers/HTML.php';

class Kiosk_App_JavaScriptRenderer extends Kiosk_App_HTMLRenderer {
	function render(&$app, &$context) {
		header('Content-type: text/javascript');
		
		$view = $this->viewPath($context);
		$context->view = $view;
		
		if (file_exists(APP_VIEWS_DIR. '/'. $view) == false) {
			$app->logger()->err("view file {$view} not found in views.");
			$context->setHTTPStatus(404, 'File not found');
		}
		
		$vars = $context->variables();
		
		$this->renderHtml($view, $vars);
	}
}

