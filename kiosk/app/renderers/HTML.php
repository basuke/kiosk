<?php

class Kiosk_App_HTMLRenderer {
	function render(&$app, &$context) {
		$view = $this->viewPath($context);
		$context->view = $view;
		
		if (file_exists(APP_VIEWS_DIR. '/'. $view) == false) {
			$app->logger()->err("view file {$view} not found in views.");
			$context->setHTTPStatus(404, 'File not found');
		}
		
		$vars = $context->variables();
		
		if (file_exists(APP_VIEWS_DIR. "/layouts/{$context->layout}.html")) {
			$this->renderHtml("layouts/{$context->layout}.html", $vars);
		} else {
			$this->renderHtml($view, $vars);
			
			if (DEVELOPMENT) {
				$this->renderHtml('elements/debug_console.html', $vars);
			}
		}
	}
	
	function viewPath(&$context) {
		$controller = $context->controller;
		$action = $context->action;
		$type = $context->type;
		
		if (!$type) {
			$type = 'html';
		}
		
		if (!$action) {
			$action = 'index';
		}
		
		$path = "{$action}.{$type}";
		
		if ($controller) {
			$path = $controller. '/'. $path;
		}
		
		return $path;
	}
	
	function renderHtml($path, $vars) {
		require_once KIOSK_LIB_DIR. '/app/Smarty.php';
		$smarty = new Kiosk_App_Smarty();
		
		foreach ($vars as $key=>$value) {
			$smarty->assign($key, $value);
		}
		
		$smarty->display($path);
	}
}

