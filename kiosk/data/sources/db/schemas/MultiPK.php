<?php

class Kiosk_Schema_DB_MultiPrimaryKeys extends Kiosk_Schema {
	function load($id, $params) {
		$condition = $id;
		
		$multi = is_pure_array($condition);
		
		if ($multi) {
			$list = $condition;
			
			if (empty($list)) return array();
			
			// 主キーが一つだけの場合には、例外として値だけの配列も認める
			if (!is_array($list[0]) and count($this->primaryKeys) == 1) {
				$params['conditions'] = array($this->primaryKeys[0] => $list);
			} else {
				$params['conditions'] = array('OR' => $list);
			}
		} else {
			$params['conditions'] = $condition;
			$params[] = 'first';
		}
		
		return $this->find($params);
	}
	
	function save(&$obj) {
		$condition = $this->conditionsForPrimaryKeys($obj);
		$columns = $this->collectValues($obj, Kiosk_WITHOUT_PRIMARY_KEYS);
		
		$updated = $this->table->update($columns, $condition);
		if ($updated > 0) return true;
		
		$columns = $this->collectValues($obj, Kiosk_INCLUDE_PRIMARY_KEYS);
		
		$inserted = $this->table->insert($columns);
		return ($inserted > 0);
	}
	
	function destroy(&$obj) {
		$condition = $this->conditionsForPrimaryKeys($obj);
		$deleted = $this->table->delete($condition);
		return ($deleted > 0);
	}
	
	function conditionsForPrimaryKeys($obj) {
		assert('$this->primaryKeys and is_array($this->primaryKeys)');
		
		$conditions = array();
		
		foreach ($this->primaryKeys as $column) {
			$conditions[$column] = $obj->$column;
		}
		
		$query = $this->createQuery();
		return $query->parseConditions($conditions);
	}
	
}

