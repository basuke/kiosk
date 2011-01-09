<?php

require_once 'Kiosk.php';

class Kiosk_Callable_TestMock {
	/* static */
	function concat($a, $b) {
		return $a. $b;
	}
	
	/* instance */
	
	var $_counter = 0;
	
	function countup($up = 1) {
		$this->_counter += $up;
		return $this->_counter;
	}
	
	function current() {
		return $this->_counter;
	}
}

class Kiosk_Callable_TestCase extends UnitTestCase {
	function testGlobalFunction() {
		$func = Kiosk::func('strtolower');
		
		$this->assertIsA($func, 'Kiosk_Callable');
		$this->assertEqual($func->call('Hello'), 'hello');
		$this->assertEqual($func->call('BINGO!'), 'bingo!');
	}
	
	function testInstanceMethod() {
		$counter =& new Kiosk_Callable_TestMock();
		$func = Kiosk::func(array(&$counter, 'countup'));
		
		$this->assertEqual($func->call(), 1);
		$this->assertEqual($counter->current(), 1); // 元のオブジェクトと同一
		
		$this->assertEqual($func->call(), 2); // 状態が保存されている
	}
	
	function testInstanceMethod2() {
		$counter =& new Kiosk_Callable_TestMock();
		$func = Kiosk::func($counter, 'countup');
		
		$this->assertEqual($func->call(), 1);
		
		if (version_compare(PHP_VERSION, '5.0.0', '>=')) {
			$this->assertEqual($counter->current(), 1); // 元のオブジェクトと同一
			$this->assertEqual($func->call(), 2); // 状態が保存されている
		} else {
			// TODO PHP 4では参照を渡さないと行けないので動かない
			// 参照を必須とすると、リテラルが渡せなくなるのでそうはしない。
			
			$this->assertEqual($counter->current(), 0); // 元のオブジェクトと違ってしまう
		}
	}
	
	function testClassMethod() {
		$func = Kiosk::func(array('Kiosk_Callable_TestMock', 'concat'));
		
		$this->assertEqual($func->call('abc', 'def'), 'abcdef');
	}
	
	function testClassMethod2() {
		$func = Kiosk::func('Kiosk_Callable_TestMock', 'concat');
		
		$this->assertEqual($func->call('STAR', 'WARS'), 'STARWARS');
	}
	
	function testCurling1() {
		$func = Kiosk::func('join');
		$func->bind(', ');
		
		$this->assertEqual($func->call(array('a', 'b', 'c')), 'a, b, c');
	}
	
	function testCurling2() {
		// バインドする引数を、コンストラクタで渡す
		$func = Kiosk::func(array('Kiosk_Callable_TestMock', 'concat'), 'foo');
		
		$this->assertEqual($func->call('bar'), 'foobar');
	}
	
	function testCurling3() {
		// 第一引数がオブジェクトなら、第２引数がメソッド名、第３引数以降がバインド引数
		$counter =& new Kiosk_Callable_TestMock();
		$func = Kiosk::func($counter, 'countup', 2); // 2ずつ増えるカウンタ
		
		$this->assertEqual($func->call(), 2);
		$this->assertEqual($func->call(), 4);
	}
	
	function testCurling4() {
		// 第一引数が存在するクラス、第２引数がメソッド名、第３引数以降がバインド引数
		$func = Kiosk::func('Kiosk_Callable_TestMock', 'concat', 'bingo_');
		
		$this->assertEqual($func->call('bongo'), 'bingo_bongo');
	}
	
	function testCurling5() {
		$func = Kiosk::func('join', '/');
		
		$this->assertEqual($func->call(array('a', 'b', 'c')), 'a/b/c');
	}
}

