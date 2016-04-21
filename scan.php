<?php

function write_detection($filename, $file_contents, $line_num, $pattern_category, $pattern_name)
{
	global $config;
	$det_file_name = $config['detections_dir']."/".$pattern_category."_".$pattern_name.".php";

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
			$lines .= "/* ********* DETECTION ******** */\n";
		$lines .= $string;
		if ($i == $line_num)
			$lines .= "/* ******** end: DETECTION ******** */\n";
	}

	$file_part = "/* *************** ".$filename." *************** */\n\n";

	$file_part .= $lines;
	$file_part .= "\n\n\n\n\n";

	file_put_contents($det_file_name, $file_part, FILE_APPEND);

	return 1;
}

global $config;
$config = array(
	'detections_dir' => "detections",
	'patterns_dir' => "patterns",
	'lines_include' => 3,
	'max_detection_strlen' => 80,
	'dangerous_strlen' => 400
);


$filename = $argv[1];
$file_contents = file($filename, FILE_SKIP_EMPTY_LINES);

if (!file_exists($config['detections_dir']))
	mkdir($config['detections_dir']);

$patterns_files = scandir($config['patterns_dir']);

$patterns = array();
foreach ($patterns_files as $pattern_file)
	if ("." != $pattern_file && ".." != $pattern_file)
		$patterns[] = parse_ini_file($config['patterns_dir']."/".$pattern_file);

foreach($file_contents as $line_num => $line)
{
	foreach($patterns as $pattern)
		if ((false !== stripos($line, $pattern['value'])) || (false !== mb_stripos($line, $pattern['value'])))
			write_detection($filename, $file_contents, $line_num, $pattern['category'], $pattern['name']);

	if (strlen($line) >= $config['dangerous_strlen'])
		write_detection($filename, $file_contents, $line_num, "long", "lines");

}
?>
