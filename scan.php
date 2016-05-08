<?php

include_once("functions.php");

function write_detection_full($directory, $filename, $file_contents, $line_num, $pattern_category, $pattern_name)
{
	global $config;
	$det_file_name = $directory."/".$pattern_category."_".$pattern_name.".php";

	if (!file_exists($directory))
		mkdir($directory, 0775, 1);

	$line_begin = $line_num - $config['lines_include'];
	if ($line_begin < 0)
		$line_begin = 0;
	$line_end = $line_num + $config['lines_include'];
	$lines_qty = count($file_contents);
	if ($line_end >= $lines_qty)
		$line_end = $lines_qty - 1;

	$lines = "";
	for ($i = $line_begin; $i <= $line_end; $i++)
	{
		$string = $file_contents[$i];
		if (strlen($string) > $config['max_detection_strlen'])
		{
			$line_arr = str_split($string, $config['max_detection_strlen']);
			$string = implode($line_arr, "\n");
		}
		if ($i == $line_num)
			$lines .= "\n/* ********* DETECTION ******** */\n";
		$lines .= $string;
		if ($i == $line_num)
			$lines .= "/* ******** end: DETECTION ******** */\n\n";
	}

	$file_part = "/* *************** ".$filename." *************** */\n\n";

	$file_part .= $lines;
	$file_part .= "\n\n\n\n\n";

	file_put_contents($det_file_name, $file_part, FILE_APPEND);

	return 1;
}

function check_exception($line, $pos1, $pos2, $pattern, $exceptions)
{
	global $config;

	if ($config['debug'])
		echo ("checking exception\n");

	$if_exception = 0;

	if (isset($exceptions[$pattern['name']]))
	{
		if (0 == $pos1)
			$pos = $pos2;
		else
			$pos = $pos1;

		foreach($exceptions[$pattern['name']] as $exception)
		{
			if ($config['debug'])
				echo ("exception: ".$exception['name']."\n");
			$exc = strtolower($exception['value']);
			$val = strtolower($pattern['value']);

			if ($config['debug'])
			{
				echo ("exc: ".$exc."\n");
				echo ("val: ".$val."\n");
			}

			$exc_pos1 = stripos($exc, $val);
			$exc_pos2 = mb_stripos($exc, $val);

			if (false !== $exc_pos1)
				$exc_pos = $exc_pos1;
			else if (false != $exc_pos2)
				$exc_pos = $exc_pos2;
			else
				$exc_pos = 0;

			if ($config['debug'])
				echo ("pos: ".$pos."\n");

			$exc_begin = $pos - $exc_pos;
			$substring = substr($line, $exc_begin, strlen($exc));

			if ($config['debug'])
				echo ("substring: ".$substring."\n");

			if (($exc == strtolower($substring)) || (mb_strtolower($exception['value']) == mb_strtolower($substring)))
			{
				$if_exception = 1;
				break;
			}
		}
	}

	if ($config['debug'] && $if_exception)
		echo ("exception\n");

	return $if_exception;
}

if (isset($argv[1]))
	$filename = $argv[1];
else
	$filename = "";
if (isset($argv[2]))
	$check_pattern = $argv[2];
else
	$check_pattern = "";

if (isset($check_pattern) && $config['debug'])
	echo ("check pattern: ".$check_pattern."\n");

$file_contents = file($filename, FILE_SKIP_EMPTY_LINES);

$patterns_files = scandir($config['patterns_dir']);
$patterns = array();
foreach ($patterns_files as $pattern_file)
	if ("." != $pattern_file && ".." != $pattern_file)
	{
		$pattern_array = parse_ini_file($config['patterns_dir']."/".$pattern_file);
		if (("" == $check_pattern) || ($check_pattern == $pattern_array['name']))
			$patterns[] = $pattern_array;
	}

$exceptions_files = scandir($config['exceptions_dir']);
$exceptions = array();
foreach ($exceptions_files as $exception_file)
	if ("." != $exception_file && ".." != $exception_file)
	{
		$exception_array = parse_ini_file($config['exceptions_dir']."/".$exception_file);
		if (("" == $check_pattern) || ($check_pattern == $exception_array['category']))
			$exceptions[$exception_array['category']][] = $exception_array;
	}

foreach($file_contents as $line_num => $line)
{
	if ($config['debug'])
		echo ("line ".$line_num."\n");
	foreach($patterns as $pattern)
	{
		if ($config['debug'])
			echo ("checking ".$pattern['name']."\n");
		$pos1 = stripos($line, $pattern['value']);
		$pos2 = mb_stripos($line, $pattern['value']);
		if ((false !== $pos1) || (false !== $pos2))
		{
			if ($config['debug'])
				echo ("detection!\n");
			if (!check_exception($line, $pos1, $pos2, $pattern, $exceptions))
				write_detection_full($config['detections_dir'], $filename, $file_contents, $line_num, $pattern['category'], $pattern['name']);
			else
				write_detection_full($config['detections_dir']."/".$config['exceptions_dir'], $filename, $file_contents, $line_num, $pattern['category'], $pattern['name']);
		}

	}

	if (strlen($line) >= $config['dangerous_strlen'])
		write_detection_full($config['detections_dir'], $filename, $file_contents, $line_num, "long", "lines");

}
?>
