<?php

function console_styles() {
	return array(
		'heading1' => "\033[1;30;46m",
		'heading2' => "\033[1;35m",
		'heading3' => "\033[1;34m",
		'option'   => "\033[40;37m",
		'command'  => "\033[1;40;37m",
		'error'    => "\033[0;31m",
		'success'  => "\033[0;32m",
		'black'  => "\033[0;30m",
		'red'    => "\033[0;31m",
		'green'  => "\033[0;32m",
		'yellow' => "\033[0;33m",
		'blue'   => "\033[0;34m",
		'purple' => "\033[0;35m",
		'cyan'   => "\033[0;36m",
		'white'  => "\033[0;37m",
		'end'    => "\033[0m",
	);
}

function console_out($data) {
	$styles = console_styles();
	
	foreach ((array) $data as $key=>$token) {
		if (is_string($key)) {
			echo $styles[$key], $token, $styles['end'];
		} else {
			echo $token;
		}
	}
}

