<?php

/*
	sql_escape($str)
	MySQL用のエスケープを行う
*/
function sql_escape($str) {
	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search, $replace, $str);
}

/*
	sql_quote($str)
	値のエスケープをして
	SQLクオートする.
*/
function sql_quote($str) {
	return "'". sql_escape($str). "'";
}

/**
	"0000/00/00 00:00:00"もしくは"0000/00/00"である書式であればUNIXのタイムスタンプで返す
	@return integer -2
	@param string $data　フォーマットされた日時
*/
function sql_datetime_to_epoch($date) {
	$date_regex = '^
					([0-9]+)[/-]([0-9]+)[/-]([0-9]+)
					(?:
						\s+
						([0-9]+):([0-9]+)
						(?:
							:([0-9]+)
						)?
					)?';
	
	if (preg_match("|$date_regex|x", $date, $match)) {
		$epoch = mktime($match[4], $match[5], $match[6], $match[2], $match[3], $match[1]);
		if ($epoch == -1) return false;
		return $epoch;
	}
	
	return null;
}

/**
	sql_epoch_to_datetime() UNIXのタイムスタンプを'Y-m-d H:i:s'のフォーマットで返す
	@param integer $epoch UNIXのタイムスタンプ
*/
function sql_epoch_to_datetime($epoch) {
	return date('Y-m-d H:i:s', $epoch);	// 2004-09-24 09:03:48
}

/*
	sqlファイルを読み込み、コメントをのぞいたSQL分の配列を返す
	
	明らかな制限: 
		;が文中に含まれるとエラー
		-- がリテラル中に含まれるとエラー
*/
function sql_read_file($path) {
	$result = array();
	
	$lines = file($path);
	$statement = '';
	
	while ($lines) {
		$line = array_shift($lines);
		$line = preg_replace('/\\s*--.*$/', '', $line);
		$line = rtrim($line);
		if (strlen($line) == 0) continue;
		
		$statement .= $line. "\n";
		if (strpos($statement, ';') !== false) {
			$result[] = $statement;
			$statement = '';
		}
	}
	
	return $result;
}

