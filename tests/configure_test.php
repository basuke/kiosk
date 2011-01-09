<?php

require_once 'Kiosk.php';

class Kiosk_MockBackend extends Kiosk_Backend {
	var $checkedValues = array();
	
	function helloWorldCheck($value) {
		// falseは設定できない
		if ($value == false) {
			return 'NO!!!';
		}
		
		$this->checkedValues[] = $value;
	}
}

class Kiosk_Configure_TestCase extends UnitTestCase {
	function testBasic() {
		$backend = new Kiosk_MockBackend();
		
		// 未定義の設定は参照もできない
		$this->expectError();
		$backend->configure('helloWorld');
		
		// 設定もできない
		$this->expectError();
		$backend->configure('helloWorld', 'foo');
		
		// ダミーの設定を追加
		$backend->_config['helloWorld'] = 'bar';
		
		// 古い値がかえってくることを確認
		$value = $backend->configure('helloWorld', 'again');
		$this->assertEqual($value, 'bar');
		
		// 設定した値がかえってくるか確認
		$value = $backend->configure('helloWorld');
		$this->assertEqual($value, 'again');
		
		// もう一度確認
		$value = $backend->configure('helloWorld');
		$this->assertEqual($value, 'again');
		
		// チェック関数が呼び出されていることを確認
		$this->assertEqual($backend->checkedValues, array('again'));
		
		// チェック関数でエラーが返ることを確認
		$this->expectError('KIOSK:CONFIG:NO!!!');
		$backend->configure('helloWorld', false);
	}
}

