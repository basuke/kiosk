<?php

require_once KIOSK_LIB_DIR. '/app/Router.php';

class Kiosk_App_RouterTestCase extends UnitTestCase {
	function testBasic() {
		$router = new Kiosk_App_Router();
		
		$router->map('/:action', array('controller'=>'app'));
		$router->map('/:controller/', array('action'=>'index'));
		$router->map('/:controller/:id', array('action'=>'view'), array('id'=>'\\d+'));
		$router->map('/:controller/:action');
		
		$cases = array(
			'/hello' => array(
				'controller'=>'app', 
				'action'=>'hello', 
			), 
			'/hello/' => array(
				'controller'=>'hello', 
				'action'=>'index', 
			), 
			'/hello/' => array(
				'controller'=>'hello', 
				'action'=>'index', 
			), 
			'/hello/123456' => array(
				'controller'=>'hello', 
				'action'=>'view', 
				'id'=>'123456', 
			), 
			'/hello/world' => array(
				'controller'=>'hello', 
				'action'=>'world', 
			), 
		);
		
		foreach ($cases as $url => $expected) {
			$params = $router->route($url);
			
			$this->assertEqual($params, $expected, "fail $url: %s");
		}
		
		foreach ($cases as $expected => $params) {
			$url = $router->url($params);
			
			$this->assertEqual($url, $expected, "fail $expected: %s");
		}
	}
}

