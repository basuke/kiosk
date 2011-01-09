<?php

/*
	新しいオブジェクトを生成する（未保存）
*/
function Kiosk_create($class, $columns=array()) {
	$data =& Kiosk_data();
	return $data->create($class, $columns);
}

/*
	渡されたハッシュの配列から、オブジェクトを生成/保存して返す
*/
function Kiosk_import($class, $args) {
	$data =& Kiosk_data();
	return $data->import($class, $args);
}

/*
	スカラーが渡された場合、その値の主キーを探し、そのオブジェクトを返す
	配列が渡された場合、その配列に含まれる主キーのオブジェクトの配列を返す
*/
function Kiosk_load($class, $id, $params=array()) {
	$data =& Kiosk_data();
	return $data->load($class, $id, $params);
}

/* 
	条件にマッチするオブジェクトの配列を返す
*/
function Kiosk_find($class, $params=array()) {
	$data =& Kiosk_data();
	return $data->find($class, $params);
}

/*
	オブジェクトを保存する
*/
function Kiosk_save(&$obj) {
	$data =& Kiosk_data();
	return $data->save($obj);
}

/*
	オブジェクトをデータベースから削除する
	オブジェクト自身はそのまま残るが、PKが設定されていない状態になる
*/
function Kiosk_destroy(&$obj) {
	$data =& Kiosk_data();
	return $data->destroy($obj);
}

/*
	オブジェクトの値、関連をロードする
*/
function Kiosk_fetch(&$obj, $name, $params=array()) {
	$data =& Kiosk_data();
	return $data->fetch($obj, $name, $params);
}

/*
	オブジェクトの関連をロードするための条件を返す
*/
function Kiosk_paramsToFetch(&$obj, $name) {
	$data =& Kiosk_data();
	return $data->paramsToFetch($obj, $name);
}

class Kiosk_Object_CRUD {
	/*
		オブジェクトを保存する
	*/
	function save() {
		return Kiosk_save($this);
	}
	
	/*
		オブジェクトをデータベースから削除する
		オブジェクト自身はそのまま残るが、PKが設定されていない状態になる
	*/
	function destroy() {
		return Kiosk_destroy($this);
	}
	
	/*
		オブジェクトの値、関連をロードする
	*/
	function fetch($name, $params=array()) {
		return Kiosk_fetch($this, $name, $params);
	}
	
	/*
		オブジェクトの関連をロードするための条件を返す
	*/
	function paramsToFetch($name) {
		return Kiosk_paramsToFetch($this, $name);
	}
}

