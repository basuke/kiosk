<?php

/**
 *	HTTP通信に関する処理を実装するクラス
 *	
 *	@access public
 */
 
 class Kiosk_Utils_HTTP {
 	/**
 	 *	HTTPリダイレクト用のヘッダーをはき出す
 	 *	
 	 *	@param $url 転送先のURL。絶対パスに変換される
 	 *	@param $temporally 一時的な転送か？
 	 *	@access public
 	 */
	function sendRedirectHeader($url, $temporally=false) {
		$status = ($temporally ? 302 : 301);
		$msg = "moved ". ($temporally ? 'temporally' : 'permanently');
		
		header("HTTP/1.1 {$status} {$msg}");
		header('Status: {$status} {$msg}');
		header('Location: '. $this->absoluteURL($url));
	}
	
	/**
	 *	HTTPステータスコードをヘッダーに追加する
	 *	
	 *	@param $status ステータスコード
	 *	@param $message ステータスメッセージ
	 *	@access public
	 */
	function sendStatusHeader($status, $message) {
		header("HTTP/1.1 $status $message");
		header("Status: $status $message");
		
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // 過去の日付
		
		switch ($status) {
			case 503:
				header('Retry-After: '. 60 * 60); // 1時間
				break;
		}
	}
	
	/**
	 *	レスポンスのコンテントタイプを設定する
	 *	
	 *	@param $type マイムタイプ
	 *	@access public
	 */
	function setContentType($type) {
		header("Content-Type: {$type}");
	}
	
	/**
	 *	URLの内容を取得しヘッダーと値の配列を返す
	 *
	 *	@param $url 出力するURL
	 *	@return array(ヘッダー, 内容) or PEAR_Error = 失敗
	 *	@access public
	 */
	function proxy($url) {
		require_once 'HTTP/Request.php';
		
		$request = new HTTP_Request($url);
		$result = $request->sendRequest();
		if (PEAR::isError($result)) {
			return $result;
		}
		
		$headers = array();
		foreach ($request->getResponseHeader() as $key => $header) {
			$headers(ucfirst($key). ': '. $header);
		}
		
		return array($headers, $request->getResponseBody());
	}
	
	/**
	 *	絶対URLを返す。
	 *	現在の環境から、足りない情報を保管する。
	 *  
	 *	$_SERVERに依存する
	 *  
	 *	@param $url URL
	 *	@return 絶対URL
	 *	@access public
	 */
	function absoluteURL($url) {
		if (strpos($url, '://') !== false) return $url;
		
		if (substr($url, 0, 1) != '/') {
			$path = $_SERVER['REQUEST_URI'];
			if (substr($path, -1, 1) == '/') {
				$path = substr($path, 0, strlen($path) -1);
			} else {
				$path = dirname($path);
			}
			
			$url = $path. '/'. $url;
		}
		
		$scheme = $this->isSSL() ? 'https' : 'http';
		return $scheme. '://'. $_SERVER['HTTP_HOST']. $url;
	}
	
	/**
	 *	現在実行中のURLを返す。
	 *	$_SERVERに依存する
	 *
	 *	@return 絶対URL
	 *	@access public
	 */
	function currentURL() {
		return $this-> absoluteURL($_SERVER['REQUEST_URI']);
	}
	
	/**
	 *	SSL接続かどうか調べる
	 *	$_SERVER依存であり、かつhttpdの実装依存。
	 *	
	 *	@return SSL接続ならtrue
	 *	@access public
	 */
	function isSSL() {
		return $_SERVER['HTTP_HTTPS'];
	}
}
 
 
