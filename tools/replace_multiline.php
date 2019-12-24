<?php

$pattern_array = file("pattern.txt", FILE_IGNORE_NEW_LINES);

$pattern = "";
foreach ($pattern_array as $k => $v)
    $pattern .= $v."\n";

$pattern = trim($pattern, "\n");

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
