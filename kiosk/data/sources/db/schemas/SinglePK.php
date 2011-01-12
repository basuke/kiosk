<?php

class Kiosk_Schema_DB_SinglePrimaryKey extends Kiosk_Schema {
	function load($id, $params) {
		$query = $this->createQuery();
		$query->setParams($params);
		$rows = $this->table->load($id, $query->params());
		
		if (is_array($id) == false) {
			if ($rows == null) return null;
			
			$rows = array($rows);
		}
		
		$objects = $query->rowsToObjects($rows);
		if ($this->afterLoad) {
			$this->applyFilter($objects, $this->afterLoad);
		}
		
		if (is_array($id) == false) {
			return array_first($objects);
		}
		
		return $objects;
	}
	
	function save(&$obj) {
		$id = $this->getId($obj);
		
		// 新規保存か？
		if (is_null($id)) {
			// 初期値を埋める
			$data =& Kiosk_data();
			$data->apply($obj, $this->defaultValues(), false);
			
			// 保存前に行う処理を実行する
			$this->_beforeSave($this, $obj);
			
			// 保存用の値のハッシュを取得する
			$columns = $this->collectValues($obj, Kiosk_INCLUDE_PRIMARY_KEYS);
			
			// テーブルに保存
			$success = $this->table->insert($columns);
			if (!$success) {
				return trigger_error(KIOSK_ERROR_RUNTIME. 'insert failed');
			}
			
			// 新規IDをオブジェクトにセット
			$this->setId($obj, $this->table->lastId());
		} else {
			// 保存前に行う処理を実行する
			$this->_beforeSave($this, $obj);
			
			$columns = $this->collectValues($obj, Kiosk_WITHOUT_PRIMARY_KEYS);
			
			$this->table->update($columns, $this->conditionForPrimaryKey($id));
		}
		
		return true;
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

