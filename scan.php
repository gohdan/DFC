<?php

$filename = $argv[1];
$file_contents = file_get_contents($filename);

//echo ($file_contents."\n");

$patterns_dir = "patterns";
$detections_dir = "detections";

$chars_include = 200;

if (!file_exists($detections_dir))
	mkdir($detections_dir);

$patterns_files = scandir($patterns_dir);

$patterns = array();
foreach ($patterns_files as $pattern_file)
	if ("." != $pattern_file && ".." != $pattern_file)
		$patterns[] = parse_ini_file($patterns_dir."/".$pattern_file);

foreach($patterns as $pattern)
{
	$pos1 = stripos($file_contents, $pattern['value']);
	//var_dump ($pos1);
	$pos2 = mb_stripos($file_contents, $pattern['value']);
	//var_dump ($pos2);

	if ((false !== $pos1) || (false !== $pos2))
	{
		//echo ("found!\n");

		$det_file_name = $detections_dir."/".$pattern['category']."_".$pattern['name'].".txt";

		if (false !== $pos1)
			$pos = $pos1;
		else
			$pos = $pos2;

		$start = $pos - $chars_include;
		if ($start < 0)
			$start = 0;
		$len = $chars_include * 2;
		$filelen = strlen($file_contents);
		if (($start + $len) > $filelen)
			$len = $filelen - $start;

		$substring = substr($file_contents, $start, $len);
		$substring = str_replace($pattern['value'], "!!!!!".$pattern['value']."!!!!!!", $substring);

		$file_part = "============= ".$filename." =============\n\n";

		$file_part .= $substring;
		$file_part .= "\n\n\n\n\n";

		file_put_contents($det_file_name, $file_part, FILE_APPEND);
	}

}







	













?>
