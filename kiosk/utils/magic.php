<?php

class FunctionCallInfo {
	var $info;
	
	function FunctionCallInfo($info) {
		$this->info = $info;
	}
	
	function get_class() {
		return ($this->info['class']);
	}
	
	function is_static_call() {
		return ($this->info['type'] == '::');
	}
}

function get_function_call_info() {
	$info = debug_backtrace();
	assert('isset($info[1])');
	
	return new FunctionCallInfo($info[1]);
}

