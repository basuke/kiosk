<?php

require_once 'kiosk/utils/url.php';
require_once 'HTTP/Request.php';

/*
	redirect_to($url) $urlにリダイレクトする
	@param $url 転送先URL
	@param $status ステータスコード。初期値（302）
*/
function redirect_to($url, $status = 302) {
	$msg = "moved ". ($status == 302 ? 'temporally' : 'permanently');
	
	header("HTTP/1.1 {$status} {$msg}");
	header('Status: {$status} {$msg}');
	header('Location: '. absolute_url($url));
	
	exit(0);
}

/*
	redirect_to_self() 自分自身にリダイレクトする
	@param $status ステータスコード。初期値（302）
	
*/
function redirect_to_self($status = 302) {
	redirect_to(self_url(), $status);
}

/*
	http_error($status, $status_message) HTTPエラーを送出する
	@param $status ステータスコード。
	@param $status_message ステータスメッセージ。
*/
function http_error($status, $status_message) {
	header("HTTP/1.1 $status $status_message");
	header("Status: $status $status_message");
	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // 過去の日付
	
	switch ($status) {
		case 503:
			header('Retry-After: '. 60 * 60); // 1時間
			break;
	}
	
	echo sprintf(
				"<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n". 
				"<html><head>\n<title>%d %s</title>\n". 
				"</head><body>\n\n<h1>%s</h1>\n". 
				"<hr>\n%s</body></html>", 
				$status, $status_message, $status_message, $_SERVER['SERVER_SIGNATURE']);
}

/*
	http_proxy_dump($url) URLの内容を出力する
	@param $url 出力するURL
	@param $ignore_headers (オプション) 無視するヘッダーの配列
	@return true = 成功 PEAR_Error = 失敗
*/
function http_proxy_dump($url, $ignore_headers=null) {
	if ($ignore_headers == null) {
		$ignore_headers = array('date');
	}
	
	$request = new HTTP_Request($url);
	$result = $request->sendRequest();
	if (PEAR::isError($result)) {
		return $result;
	}
	
	$headers = $request->getResponseHeader();
	foreach ($ignore_headers as $key) {
		unset($headers[strtolower($key)]);
	}
	
	foreach ($headers as $key => $header) {
		header(ucfirst($key). ': '. $header);
	}
	
	echo $request->getResponseBody();
	return true;
}

/*
	set_content_type($type) レスポンスのコンテントタイプを設定する
*/
function set_content_type($type) {
	header("Content-Type: {$type}");
}

