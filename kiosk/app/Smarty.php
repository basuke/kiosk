<?php

assert('class_exists("Smarty"); /* Smarty must be included */');

require_once KIOSK_LIB_DIR. '/form/Smarty.php';

class Kiosk_App_Smarty extends Smarty {
	function Kiosk_App_Smarty() {
		$this->Smarty();
		
		$app =& Kiosk::app();
		
		$this->template_dir = APP_VIEWS_DIR;
		$this->compile_dir  = APP_TMP_DIR. '/templates/';
		$this->config_dir   = APP_VIEWS_DIR. '/config/';
		$this->cache_dir    = APP_CACHE_DIR. '/';
		$this->plugins_dir = array('plugins');
		$this->default_template_handler_func = array($this, 'missingTemplate');
		
		$this->caching = false;
		
		if (DEVELOPMENT) {
			$this->assign('debug', Debug::getInstance());
		}
		
		$form = new Kiosk_Form_Smarty();
		$form->register($this);
	}
	
	function missingTemplate($type, $name, $source, $timestamp, $smarty) {
		return false;
	}
}

