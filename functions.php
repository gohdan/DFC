<?php

global $config;
$config = array(
	'debug' => 0, // 1 - show debug info, 0 - no debug info
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
	'min_spaces_proportion' => '0.01'
);

function get_files_list ($dir, $files)
{
	global $config;

	if ($config['debug'])
		echo ("=== get_files_list ===\n");
	$dir = rtrim($dir, $config['slash']);
	if ($config['debug'])
	{
		echo ("dir: ".$dir."\n");
		echo ("input files:\n");
		print_r($files);
	}
	$curdir = opendir($dir);
 	while(($file = readdir($curdir)))
	{
		if ($config['debug'])
			echo ("listing ".$file."\n");
		$fullname = $dir.$config['slash'].$file;
		if ($config['debug'])
			echo ("fullname: ".$fullname."\n");
		if (is_file ($fullname))
		{
			if ($config['debug'])
				echo ("is file, adding to array\n");
			$files[] = $fullname;
		}
		else
		{
			if ($config['debug'])
				echo ("not file\n");

		    if (is_dir ($fullname) )
			{
				if ($config['debug'])
					echo ("is directory\n");

				if (($file == ".") || ($file == ".."))
				{
					if ($config['debug'])
						echo ("no real directory, skipping\n");
				}
				else
				{
					if ($config['debug'])
						echo ("scanning directory\n");
					$new_files = get_files_list($fullname, $files);
					if ($config['debug'])
					{
						echo ("new files:\n");
						print_r($new_files);
					}
					$files = $files + $new_files;
				}
			}
		}
	}
	closedir($curdir);
	if ($config['debug'])
	{
		echo ("output files:\n");
		print_r($files);
	}
	return $files;
}

function backup_infected($s_file)
{
	global $config;
	
	$basedir = __DIR__.$config['slash'];

	$file = str_replace ($basedir, "", $s_file);
	$path_arr = explode($config['slash'], $file);

	$filename = array_pop($path_arr);

	$path = "";
	foreach($path_arr as $k => $v)
	{
		if (0 == $k)
			$v = $config['infected_dir'];

		$path .= $v.$config['slash'];

		if (!file_exists($path))
			mkdir($path);
	}

	$s_file_new = $path.$config['slash'].$filename;
	if (!file_exists($s_file_new))
		copy ($s_file, $s_file_new);

	return 1;
}

function write_detection($filename, $info)
{
	global $config;
	$det_file_name = $config['detections_dir'].$config['slash'].$filename;

	if (!file_exists($config['detections_dir']))
		mkdir($config['detections_dir'], 0775, 1);

	file_put_contents($det_file_name, $info."\n", FILE_APPEND);

	return 1;
}

function write_file_del($filename)
{
	global $config;

	file_put_contents($config['files_del'], $filename."\n", FILE_APPEND);

	return 1;
}

function write_file_repl($filename)
{
	global $config;

	file_put_contents($config['files_repl'], $filename."\n", FILE_APPEND);

	return 1;
}

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


?>
