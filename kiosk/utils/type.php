<?php

/*
	get_keyed_value($obj, $key)
	
	渡された値の $key に相当する値を返す。
	値の種別により取得の仕方が異なる。
	配列ならキーとして
	オブジェクトならプロパティを
	それ以外は null 
*/
function get_keyed_value($onj, $key) {
	if (is_array($onj)) {
		return isset($onj[$key]) ? $onj[$key] : null;
	}
	
	if (is_object($onj)) {
		return isset($onj->$key) ? $onj->$key : null;
	}
	
	return null;
}

