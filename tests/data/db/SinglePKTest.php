<?php

require_once 'Kiosk.php';
require_once KIOSK_HOME. '/tests/samples/DB.php';
require_once KIOSK_HOME. '/tests/samples/Classes.php';

class Kiosk_Data_DBSource_SinglePKTestCase extends UnitTestCase {
	function testSinglePK() {
		$db =& open_test_database();
		sample_database_schemaq($db);
		Kiosk_reset();
		
		// 準備
		
		Item::bind($db, array());
		Item::import(array(
			array('col1'=>100, 'title'=>'iPod'), 
			array('col1'=>200, 'title'=>'iPhone'), 
		));
		
		// 指定なし（標準）状態でidが使われることを確認
		Kiosk_reset();
		
		Item::bind($db, array());
		
		$item = Item::load(1);
		$this->assertEqual($item->title, 'iPod');
		
		// 指定なし（標準）状態でidが使われることを確認
		Kiosk_reset();
		
		Item::bind($db, array('primaryKey'=>'col1'));
		
		$item = Item::load(200);
		$this->assertEqual($item->title, 'iPhone');
		
		// お片づけ
		close_test_database($db);
	}
}

