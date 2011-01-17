<?php

class KioskSmarty extends Smarty {
	function KioskSmarty() {
		$this->Smarty();
		
		$app =& Kiosk::app();
		
		$this->template_dir = APP_VIEWS_DIR;
		$this->compile_dir  = APP_TMP_DIR. '/templates/';
		$this->config_dir   = APP_VIEWS_DIR. '/config/';
		$this->cache_dir    = APP_CACHE_DIR. '/';
		$this->plugins_dir = array('plugins');
		$this->default_template_handler_func = array($app, 'missingTemplate');
		
		$this->caching = false;
		
		if (DEVELOPMENT) {
			$this->assign('debug', Debug::getInstance());
		}
	}
}

