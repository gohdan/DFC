<?php

function write_detection($directory, $filename, $file_contents, $line_num, $pattern_category, $pattern_name)
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
	$if_exception = 0;

	if (isset($exceptions[$pattern['category']]))
	{
		if (0 == $pos1)
			$pos = $pos2;
		else
			$pos = $pos1;

		foreach($exceptions[$pattern['category']] as $exception)
		{
			$exc_pos1 = stripos($exception['value'], $pattern['value']);
			$exc_pos2 = mb_stripos($exception['value'], $pattern['value']);

			if (false !== $exc_pos1)
				$exc_pos = $exc_pos1;
			else if (false != $exc_pos2)
				$exc_pos = $exc_pos2;
			else
				$exc_pos = 0;
			$exc_begin = $pos - $exc_pos;
			$substring = substr($line, $exc_begin, strlen($exception['value']));

			if ((strtolower($exception['value']) == strtolower($substring)) || (mb_strtolower($exception['value']) == mb_strtolower($substring)))
				$if_exception = 1;
		}
	}

	return $if_exception;
}

global $config;
$config = array(
	'detections_dir' => "detections",
	'patterns_dir' => "patterns",
	'exceptions_dir' => "exceptions",
	'lines_include' => 3,
	'max_detection_strlen' => 150,
	'dangerous_strlen' => 400
);

$filename = $argv[1];
$file_contents = file($filename, FILE_SKIP_EMPTY_LINES);

$patterns_files = scandir($config['patterns_dir']);
$patterns = array();
foreach ($patterns_files as $pattern_file)
	if ("." != $pattern_file && ".." != $pattern_file)
		$patterns[] = parse_ini_file($config['patterns_dir']."/".$pattern_file);

$exceptions_files = scandir($config['exceptions_dir']);
$exceptions = array();
foreach ($exceptions_files as $exception_file)
	if ("." != $exception_file && ".." != $exception_file)
	{
		$exception_array = parse_ini_file($config['exceptions_dir']."/".$exception_file);
		$exceptions[$exception_array['category']][] = $exception_array;
	}

foreach($file_contents as $line_num => $line)
{
	foreach($patterns as $pattern)
	{
		$pos1 = stripos($line, $pattern['value']);
		$pos2 = mb_stripos($line, $pattern['value']);
		if ((false !== $pos1) || (false !== $pos2))
			if (!check_exception($line, $pos1, $pos2, $pattern, $exceptions))
				write_detection($config['detections_dir'], $filename, $file_contents, $line_num, $pattern['category'], $pattern['name']);
			else
				write_detection($config['detections_dir']."/".$config['exceptions_dir'], $filename, $file_contents, $line_num, $pattern['category'], $pattern['name']);

	}

	if (strlen($line) >= $config['dangerous_strlen'])
		write_detection($config['detections_dir'], $filename, $file_contents, $line_num, "long", "lines");

}
?>
