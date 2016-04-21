<?php

//$chars_include = 200;
$lines_include = 3;
$max_detection_strlen = 80;

$filename = $argv[1];
//$file_contents = file_get_contents($filename);
$file_contents = file($filename, FILE_SKIP_EMPTY_LINES);

//echo ($file_contents."\n");

$patterns_dir = "patterns";
$detections_dir = "detections";

if (!file_exists($detections_dir))
	mkdir($detections_dir);

$patterns_files = scandir($patterns_dir);

$patterns = array();
foreach ($patterns_files as $pattern_file)
	if ("." != $pattern_file && ".." != $pattern_file)
		$patterns[] = parse_ini_file($patterns_dir."/".$pattern_file);

foreach($file_contents as $line_num => $line)
	foreach($patterns as $pattern)
	{
		$pos1 = stripos($line, $pattern['value']);
		//var_dump ($pos1);
		$pos2 = mb_stripos($line, $pattern['value']);
		//var_dump ($pos2);

		if ((false !== $pos1) || (false !== $pos2))
		{
			//echo ("found!\n");

			$det_file_name = $detections_dir."/".$pattern['category']."_".$pattern['name'].".php";

			/*
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
			*/

			$line_begin = $line_num - $lines_include;
			if ($line_begin < 0)
				$line_begin = 0;
			$line_end = $line_num + $lines_include;
			$lines_qty = count($file_contents);
			if ($line_end >= $lines_qty)
				$line_end = $lines_qty - 1;

			$lines = "";
			for ($i = $line_begin; $i <= $line_end; $i++)
			{
				$string = $file_contents[$i];
				if (strlen($string) > $max_detection_strlen)
				{
					$line_arr = str_split($string, $max_detection_strlen);
					$string = implode($line_arr, "\n");
				}
				if ($i == $line_num)
					$lines .= "/* ****** DETECTION ***** */\n";
				$lines .= $string;
				if ($i == $line_num)
					$lines .= "/* ***** end: DETECTION ***** */\n";
			}

			$file_part = "/* *************** ".$filename." *************** */\n\n";

			//$file_part .= $substring;
			$file_part .= $lines;
			$file_part .= "\n\n\n\n\n";

			file_put_contents($det_file_name, $file_part, FILE_APPEND);
		}

	}

?>
