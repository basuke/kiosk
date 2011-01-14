<?php

require_once 'Kiosk.php';

function K_OP1($op, $exp) {
	return array($op => $exp);
}

function K_OP($op, $key, $value) {
	return K_OP1($op, array($key, $value));
}

function K_EQ($key, $value) {
	return K_OP('=', $key, $value);
}

function K_IN($key, $value) {
	return K_OP('IN', $key, $value);
}

function K_AND(/* , ... */) {
	$exps = func_get_args();
	return K_OP1('&', $exps);
}

function K_OR(/* , ... */) {
	$exps = func_get_args();
	return K_OP1('|', $exps);
}

function K_NOT($exp) {
	return K_OP1('!', $exp);
}

class Kiosk_Data_Query_TestCase extends UnitTestCase {
	function setUp() {
		Kiosk_reset();
		$this->query =& new Kiosk_Data_Query();
	}
	
	function assertConditionCases($cases) {
		$query =& $this->query;
		
		foreach ($cases as $case=>$test) {
			list($test, $expected) = $test;
			
			if (is_null($expected)) {
				$this->assertNull(
					$query->parseConditions($test), 
					"faild {$case}: %s");
			} else {
				$this->assertEqual(
					$query->parseConditions($test), 
					$expected, 
					"faild {$case}: %s");
			}
		}
	}
	
	function testConditionsBasic() {
		$cases = array(
			'simple string' => array(
				'a=1', 
				'a=1',
			),
			'simple string in array' => array(
				array('a=1'), 
				'a=1',
			),
			'multi string in array' => array(
				array('a=1', "b='ABC'"), 
				K_AND(
					'a=1', 
					"b='ABC'"
				),
				"(a=1 AND b='ABC')",
			),
			'OR connections' => array(
				array('OR'=>array('a=1', "b='ABC'")), 
				K_OR(
					'a=1', 
					"b='ABC'"
				),
				"(a=1 OR b='ABC')",
			),
			'AND connections' => array(
				array('AND'=>array('a=1', "b='ABC'")), 
				K_AND(
					'a=1', 
					"b='ABC'"
				),
				"(a=1 AND b='ABC')",
			),
			'simple hash' => array(
				array('a'=>1), 
				K_EQ('a', 1),
				'a = 1',
			),
			'multi hash values' => array(
				array('a'=>1, 'b'=>'abc', 'c'=>true, 'd'=>3.14), 
				K_AND(
					K_EQ('a', 1),
					K_EQ('b', 'abc'),
					K_EQ('c', true), 
					K_EQ('d', 3.14)
				),
				"(a = 1 AND b = 'abc' AND c = TRUE AND d = 3.14)",
			),
			'complex 1' => array(
				array(
					'a'=>1, 
					"b LIKE 'abc'", 
					array('OR' => array('c'=>true, 'd'=>3.14)), 
					array('OR' => array('e'=>'foo')), 
				), 
				K_AND(
					K_EQ('a', 1),
					"b LIKE 'abc'",
					K_OR(
						K_EQ('c', true), 
						K_EQ('d', 3.14)
					),
					K_EQ('e', 'foo')
				),
				"(a = 1 AND b LIKE 'abc' AND (c = TRUE OR d = 3.14) AND e = 'foo')",
			),
		);
		
		$this->assertConditionCases($cases);
	}
	
	function testConditionsComplex() {
		$cases = array(
			'simple "in"' => array(
				array('a' => array(1, 2, 3)), 
				K_IN('a', array(1, 2, 3)),
			),
			
			'not "in"' => array(
				array('NOT' => array('a' => array(1, 2, 3))), 
				K_NOT(K_IN('a', array(1, 2, 3))),
			),
			
			'is null' => array(
				array('a' => null), 
				K_OP('IS', 'a', null),
			),
			
			'NOT (a IS NULL)' => array(
				array('NOT' => array('a' => null)), 
				K_NOT(K_OP('IS', 'a', null))
			),
			
			'from cake condition' => array(
				// http://book.cakephp.org/ja/view/1030/Complex-Find-Conditions
				array(
					'OR' => array(
						array('name' => 'Future Holdings'),
						array('name' => 'Steel Mega Works'), 
					),
					'AND' => array(
						array(
							'OR'=>array(
								array('status' => 'active'),
								'NOT'=>array(
									array('status'=> array('inactive', 'suspended'))
								)
							)
						)
					)
				),
				K_AND(
					K_OR(
						K_EQ('name', 'Future Holdings'), 
						K_EQ('name', 'Steel Mega Works')
					), 
					K_OR(
						k_EQ('status', 'active'), 
						K_NOT(
							K_IN('status', array('inactive', 'suspended'))
						)
					)
				), 
				"((name = 'Future Holdings' OR name = 'Steel Mega Works') AND (status = 'active' OR NOT (status IN ('inactive','suspended'))))",
			),
			
		);
		
		$this->assertConditionCases($cases);
	}
	
	function testConditionsOperators() {
		$cases = array(
			"a <> 'hello'" => array(
				array('NOT a' => 'hello'), 
				K_NOT(K_EQ('a', 'hello')), 
			), 
			"a <> 'hello'" => array(
				array('!a' => 'hello'), 
				K_NOT(K_EQ('a', 'hello')), 
			), 
			"a NOT IN (1,2,3)" => array(
				array('NOT a' => array(1,2,3)), 
				K_NOT(K_IN('a', array(1,2,3)))
			), 
			
			"a < 100" => array(
				array('a <' => 100), 
				K_OP('<', 'a', 100)
			), 
			
			"a LIKE 'Hello world'" => array(
				array('a LIKE' => 'Hello world'), 
				K_OP('LIKE', 'a', 'Hello world')
			), 
		);
		
		$this->assertConditionCases($cases);
	}
	
	function testConditionsResultNull() {
		$cases = array(
			'empty string' => array(
				'', 
				null
			), 
			'empty array' => array(
				array(), 
				null
			), 
			'array of empty string' => array(
				array('', '', ''), 
				null
			), 
			'array or empty array' => array(
				array(array(), array()), 
				null
			), 
		);
		
		$this->assertConditionCases($cases);
	}
	
	function testOrder() {
		$query =& $this->query;
		
		$cases = array(
			'single ASC string' => array(
				'hello',
				array(
					array('hello', false), 
				),
				// 'hello',
			), 
			'single string with _' => array(
				'-hello_world',
				array(
					array('hello_world', true), 
				),
				// 'hello_world DESC',
			), 
			'multi DESC string' => array(
				' hello DESC, -world',
				array(
					array('hello', true), 
					array('world', true), 
				),
				// 'hello DESC,world DESC',
			), 
			'multi desc array' => array(
				array('hello ', '-world'),
				array(
					array('hello', false), 
					array('world', true), 
				),
				// 'hello,world DESC',
			), 
			'complex' => array(
				'hello ASC, world desc   , bingo   DeSC, -bongo DESC',
				array(
					array('hello', false), 
					array('world', true), 
					array('bingo', true), 
					array('bongo', false), 
				),
				// 'hello,world DESC,bingo DESC,bongo',
			), 
		);
		
		foreach ($cases as $case=>$test) {
			$this->assertEqual(
				$query->parseOrder($test[0]), 
				$test[1], 
				"faild {$case} %s");
		}
	}
	
	function testOrderError() {
		$query =& $this->query;
		
		$cases = array(
			'hello BINGO', 
			'+hello', 
		);
		
		foreach ($cases as $case=>$test) {
			$this->expectError();
			$result = $query->parseOrder($test);
		}
	}
}

