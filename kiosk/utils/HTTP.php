<?php

/**
 *	HTTP通信に関する処理を実装するクラス
 *	
 *	@access public
 */
 
 class Kiosk_Utils_HTTP {
	function renderRedirectResponse($url, $temporally=false) {
		$status = ($temporally ? 302 : 301);
		$msg = "moved ". ($temporally ? 'temporally' : 'permanently');
		
		header("HTTP/1.1 {$status} {$msg}");
		header('Status: {$status} {$msg}');
		header('Location: '. $this->absoluteUrl($url));
	}
	
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
 }
 
 