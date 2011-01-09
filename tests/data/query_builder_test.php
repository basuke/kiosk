<?php

require_once 'Kiosk.php';

class Kiosk_Data_Query_TestCase extends UnitTestCase {
	function setUp() {
		Kiosk_reset();
		$this->query =& new Kiosk_Data_Query();
	}
	
	function assertConditionCases($cases, $show=false) {
		$query =& $this->query;
		
		foreach ($cases as $case=>$test) {
			list($test, $expected) = $test;
			
			if ($show) {
				var_dump(array('expected' => $expected, 'value' => $query->parseConditions($test), ));
			}
			
			if (is_null($expected)) {
				$this->assertNull(
					$query->parseConditions($test), 
					"faild {$case}");
			} else {
				$this->assertEqual(
					$query->parseConditions($test), 
					$expected, 
					"faild {$case}");
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
				"(a=1 AND b='ABC')",
			),
			'OR connections' => array(
				array('OR'=>array('a=1', "b='ABC'")), 
				"(a=1 OR b='ABC')",
			),
			'AND connections' => array(
				array('AND'=>array('a=1', "b='ABC'")), 
				"(a=1 AND b='ABC')",
			),
			'simple hash' => array(
				array('a'=>1), 
				'a=1',
			),
			'multi hash values' => array(
				array('a'=>1, 'b'=>'abc', 'c'=>true, 'd'=>3.14), 
				"(a=1 AND b='abc' AND c=TRUE AND d=3.14)",
			),
			'complex 1' => array(
				array(
					'a'=>1, 
					"b LIKE 'abc'", 
					array('OR' => array('c'=>true, 'd'=>3.14)), 
					array('OR' => array('e'=>'foo')), 
				), 
				"(a=1 AND b LIKE 'abc' AND (c=TRUE OR d=3.14) AND e='foo')",
			),
		);
		
		$this->assertConditionCases($cases);
	}
	
	function testConditionsComplex() {
		$cases = array(
			'simple "in"' => array(
				array('a' => array(1, 2, 3)), 
				'a IN (1,2,3)',
			),
			
			'not "in"' => array(
				array('NOT' => array('a' => array(1, 2, 3))), 
				'NOT (a IN (1,2,3))',
			),
			
			'is null' => array(
				array('a' => null), 
				'a IS NULL',
			),
			
			'is not null' => array(
				array('NOT' => array('a' => null)), 
				'NOT (a IS NULL)',
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
				"((name='Future Holdings' OR name='Steel Mega Works') AND (status='active' OR NOT (status IN ('inactive','suspended'))))",
			),
			
		);
		
		$this->assertConditionCases($cases);
	}
	
	function testConditionsOperators() {
		$cases = array(
			'a' => array(
				array('NOT a' => 'hello'), 
				"a<>'hello'"
			), 
			'b' => array(
				array('!a' => 'hello'), 
				"a<>'hello'"
			), 
			'c' => array(
				array('NOT a' => array(1,2,3)), 
				"a NOT IN (1,2,3)"
			), 
			'd' => array(
				array('a <' => 100), 
				"a < 100"
			), 
			'e' => array(
				array('a LIKE' => 'Hello world'), 
				"a LIKE 'Hello world'"
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
	
	function testConditionsSyntaxError() {
		$query =& $this->query;
		
		$cases = array(
			array('NOT a <' => 100), 
		);
		
		foreach ($cases as $case=>$test) {
			$this->expectError();
			$result = $query->parseConditions($test);
		}
	}
	
	function testOrder() {
		$query =& $this->query;
		
		$cases = array(
			'single ASC string' => array(
				'hello',
				'hello',
			), 
			'single string with _' => array(
				'-hello_world',
				'hello_world DESC',
			), 
			'multi DESC string' => array(
				' hello DESC, -world',
				'hello DESC,world DESC',
			), 
			'multi desc array' => array(
				array('hello ', '-world'),
				'hello,world DESC',
			), 
			'complex' => array(
				'hello ASC, world desc   , bingo   DeSC, -bongo DESC',
				'hello,world DESC,bingo DESC,bongo',
			), 
		);
		
		foreach ($cases as $case=>$test) {
			$this->assertEqual(
				$query->parseOrder($test[0]), 
				$test[1], 
				"faild {$case}");
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

