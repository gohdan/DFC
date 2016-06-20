<?php

$pattern = "<script language=\"javascript\" charset=\"UTF-8\" type=\"text/javascript\" src=\"http://example.org/javascript.js\"></script>";


if (!isset($argv[1]))
	echo ("Pleas give file name to replace\n");
else
{
	$filename = $argv[1];

	$file_contents = file_get_contents($filename);

	$new_file_contents = str_replace ($pattern, "", $file_contents);

	file_put_contents($filename, $new_file_contents);
}

?>
