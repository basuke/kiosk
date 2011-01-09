<?php

/*
	set_flash_message(...) フラッシュメッセージをセットする
*/

define('FLASH_MESSAGE_KEY', 'flash');

function set_flash_message($msg) {
	$_SESSION[APP][FLASH_MESSAGE_KEY] = $msg;
}

/*
	set_flash_message(...) フラッシュメッセージをセットする
*/
function get_and_clear_flash_message() {
	$msg = $_SESSION[APP][FLASH_MESSAGE_KEY];
	unset($_SESSION[APP][FLASH_MESSAGE_KEY]);
	return $msg;
}

