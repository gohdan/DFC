<?php

include("../includes/config.php");
include("../includes/functions.php");

if (isset($argv[1]))
	$scan_target = $argv[1];
else
{
	echo ("Please give directory to scan\n");
	exit;
}

if (isset($argv[2]))
	$version = $argv[2];
else
{
	echo ("Please give version tag\n");
	exit;
}

if ($config['debug'])
{
	echo ("directory: ".$scan_target."\n");
	echo ("version: ".$version."\n");
}

$files = get_files_list($scan_target, array());

$files_qty = count($files);

$i = 1;

foreach ($files as $filename)
{
	echo ($i." / ".$files_qty." ".$filename."\n");
	$file_contents_string = file_get_contents($filename);
	$hash = md5(trim($file_contents_string));
	if ($config['debug'])
		echo ($hash . " " . $version ."\n");

	$path_arr = explode($config['slash'], $filename);
	$fname = array_pop($path_arr);

	$path = "";
	foreach($path_arr as $k => $v)
	{
		if (0 == $k)
			$v = "hashes";

		$path .= $v.$config['slash'];

		if (!file_exists($path))
			mkdir($path);
	}

	$hash_file = $path.$config['slash'].$fname;
	if (!file_exists($hash_file))
		file_put_contents($hash_file, $version." = ". $hash."\n");
	else
	{
		$has_hashes = parse_ini_file($hash_file);
		if ($config['debug'])
			print_r($has_hashes);
		if (in_array($hash, $has_hashes))
			echo ("already have hash\n");
		else
			file_put_contents($hash_file, $version." = ". $hash."\n", FILE_APPEND);
	}

	$i++;
}

?>
