<?php

global $config;
$config = array(
	'debug' => 0, // 1 - show debug info, 0 - no debug info
	'check_pattern' => '', // pattern to check
	'infected_dir' => "virii",
	'detections_dir' => "detections",
	'patterns_dir' => "patterns",
	'exceptions_dir' => "exceptions",
	'files_del' => "files_del.txt",
	'files_repl' => "files_repl.txt",
	'lines_include' => 3,
	'max_detection_strlen' => 150,
	'dangerous_strlen' => 400,
	'slash' => "/",
	'min_spaces_proportion' => '0.01',
	'php_close_tag' => '?>',
	'big_file_size' => '1048576',
	'strange_symbols' => array(
		'0' => array (
			'name' => 'at',
			'value' => '@',
			'dang_qty' => '3'
		),
		'1' => array (
			'name' => 'dollar',
			'value' => '$',
			'dang_qty' => '8'
		),
		'2' => array (
			'name' => 'brace_curly_left',
			'value' => '{',
			'dang_qty' => '8'
		),
		'3' => array (
			'name' => 'brace_curly_right',
			'value' => '}',
			'dang_qty' => '8'
		),
		'4' => array (
			'name' => 'bracket_square_left',
			'value' => '[',
			'dang_qty' => '10'
		),
		'5' => array (
			'name' => 'bracket_square_right',
			'value' => ']',
			'dang_qty' => '10'
		),
	),
	'filetypes' => array(
		'php',
		'htaccess',
		'js',
		'jpg',
		'png',
		'gif',
		'bmp',
		'html',
		'css',
		'po',
		'mo',
		'ini',
		'txt',
		'md',
		'xml',
		'sql',
		'tpl'
	)
);


?>
