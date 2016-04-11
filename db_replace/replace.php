<?php

$patterns_file = "patterns.txt";
$db_file = "db.sql";
$db_new_file = "db_new.sql";

$patterns = file($patterns_file);

$dump = file_get_contents($db_file);

/*
$dump_new = preg_replace($patterns, "", $dump, -1, $count);
file_put_contents($db_new_file, $dump_new);
echo ($count ." replacements\n");
*/

$count_all = 0;

foreach ($patterns as $pattern)
{
	echo $pattern;

	$dump = preg_replace($pattern, "", $dump, -1, $count);

	echo ($count ." replacements\n");

	$count_all = $count_all + $count;
}

file_put_contents($db_new_file, $dump);

echo ($count_all ." total");

?>

