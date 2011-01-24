<?php

class Kiosk_Schema_DB_SinglePrimaryKey extends Kiosk_Schema {
	function finalize() {
		parent::finalize();
	}
	
	function primaryKeyName() {
		$name = parent::primaryKeyName();
		if ($name) {
			return $name;
		}
		
		return 'id';
	}
	
	function conditionForPrimaryKey($id) {
		return $this->primaryKeyName(). '='. $this->table->db->literal($id);
	}
	
	function load($id, $params) {
		$rows = $this->_load($id, $params);
		
		if (is_array($id) == false) {
			if ($rows == null) return null;
			
			$rows = array($rows);
		}
		
		$objects = $this->rowsToObjects($rows, $query);
		if ($this->afterLoad) {
			$this->applyFilter($objects, $this->afterLoad);
		}
		
		if (is_array($id) == false) {
			return array_first($objects);
		}
		
		return $objects;
	}
	
	function _load($id, $params=array()) {
		if (empty($id)) return is_array($id) ? array() : null;
		
		$query = $this->createQuery($params);
		
		$id_column = $this->table->primaryKeyName();
		assert('$id_column');
		
		if (is_array($id)) {
			$query->conditions = $id_column. ' IN '. $this->table->db->literal($id);
		} else {
			$query->conditions = $this->conditionForPrimaryKey($id);
		}
		
		$rows = $query->fetch();
		
		if (! is_array($id)) {
			return array_first($rows);
		}
		
		$objects = array();
		foreach ($rows as $object) {
			$objects[$object[$id_column]] = $object;
		}
		
		$result = array();
		foreach ($id as $id) {
			$result[$id] = $objects[$id];
		}
		
		return $result;
	}
	
	function save(&$obj) {
		$id = $this->getId($obj);
		
		// 保存前に行う処理を実行する
		$this->_beforeSave($this, $obj);
		
		// 新規保存か？
		if (! is_null($id)) {
			$columns = $this->collectValues($obj, Kiosk_WITHOUT_PRIMARY_KEYS);
			
			$cond = $this->conditionForPrimaryKey($id);
			$success = $this->table->update($columns, $cond);
			if ($success) return true;
		}
		
		// 初期値を埋める
		$data =& Kiosk_data();
		$data->apply($obj, $this->defaultValues(), false);
		
		// 保存用の値のハッシュを取得する
		$columns = $this->collectValues($obj, Kiosk_INCLUDE_PRIMARY_KEYS);
		
		// テーブルに保存
		$success = $this->table->insert($columns);
		if ($success) {
			if (is_null($id)) {
				// 新規IDをオブジェクトにセット
				$this->setId($obj, $this->table->lastId());
			}
			
			return true;
		}
		
		return trigger_error(KIOSK_ERROR_RUNTIME. 'insert failed');
	}
	
	function destroy(&$obj) {
		$id = $this->getId($obj);
		if (is_null($id)) {
			return trigger_error(KIOSK_ERROR_RUNTIME. 'cannnot destroy unsaved object');
		}
		
		// テーブルから削除
		$count = $this->table->delete($this->conditionForPrimaryKey($id));
		if (!$count) {
			return trigger_error(KIOSK_ERROR_RUNTIME. 'delete failed');
		}
		
		// オブジェクトIDをnullにセット
		$this->setId($obj, null);
		return true;
	}
	
}

