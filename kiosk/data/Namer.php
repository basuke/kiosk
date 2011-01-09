<?php

class Kiosk_Namer {
	function classNameToTableName($class_name) {
		return strtolower($class_name);
	}
	
	function classNameToColumnName($class_name) {
		$table_name = $this->classNameToTableName($class_name);
		return $this->tableNameToColumnName($table_name);
	}
	
	function tableNameToColumnName($table_name) {
		return $table_name. '_id';
	}
	
	function pluralize($word) {
		return $word. 's';
	}
	
	function singularize($word) {
		return preg_replace('/s$/', '', $word);
	}
}

