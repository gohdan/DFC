<?php

function get_memory_limit()
{
	global $config;

	$memory_limit = ini_get("memory_limit");

	$memory_limit_unit = substr($memory_limit, -1);
	$memory_limit_size = substr($memory_limit, 0, strlen($memory_limit) - 1);

	switch($memory_limit_unit)
	{
		default:
			if (is_numeric($memory_limit))
				$memory_limit_real = $memory_limit;
			else
				$memory_limit_real = $config['big_file_size'];
		break;

		case "K":
			$memory_limit_real = $memory_limit_size * 1024;
		break;

		case "M":
			$memory_limit_real = $memory_limit_size * 1024 * 1024;
		break;

		case "G":
			$memory_limit_real = $memory_limit_size * 1024 * 1024 * 1024;
		break;
	}

	return $memory_limit_real;
}

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

function update_file($filename, $new_file_contents)
{
	global $config;

	backup_infected($filename);

	if (strlen(trim($new_file_contents)) == 0)
	{
		if ($config['debug'])
			echo ("file becomes empty, deleting\n");
		write_file_del($filename);
		unlink($filename);
	}
	else
	{
		if ($config['debug'])
			echo ("overwriting file\n");
		write_file_repl($filename);
		file_put_contents($filename, $new_file_contents);
	}
	return 1;
}

function remove_last_line($filename)
{
	global $config;

	$file_contents = file($filename);
	$file_contents_string = file_get_contents($filename);

	$line = array_pop($file_contents);

	write_detection ("last_lines.txt", $filename);
	write_detection ("last_lines.txt", substr($line, 0, 60) . " ... " . substr($line, -60));
	write_detection ("last_lines.txt", "\n");


	$new_file_contents = "";
	foreach($file_contents as $k => $v)
		$new_file_contents .= $v;

	update_file($filename, $new_file_contents);

	return 1;
}

function check_hash($file_contents_string, $filename)
{
	global $config;
	global $hashes;

	$hash = md5(trim($file_contents_string));

	$known_hashes_file = str_replace($config['scan_target'], "known_files/hashes/", $filename);

	if (file_exists($known_hashes_file))
	{
		$known_hashes = parse_ini_file($known_hashes_file);

		if (in_array($hash, $known_hashes))
		{
			$result = 1;
			$version = array_search($hash, $known_hashes);
			write_detection("files_known.txt", $filename." ".$version);
		}
		else
		{
			$result = 0;
			write_detection("files_changed.txt", $filename);
			$hashes[$hash][] = $filename;
		}
	}
	else
	{
		$result = 0;
		write_detection("files_unknown.txt", $filename);
		$hashes[$hash][] = $filename;
	}
	
	return $result;
}

function check_php_presence($file_contents_string, $filetype, $filename)
{
	global $config;

	$pos = stripos($file_contents_string, "php");
	if (false !== $pos)
	{
		$begin = $pos - 10;
		if ($begin < 0)
			$begin = 0;
		write_detection ("php_in_".$filetype.".txt", $filename);
		write_detection ("php_in_".$filetype.".txt", substr($file_contents_string, $begin, 20));
		write_detection ("php_in_".$filetype.".txt", "\n");
	}
}


?>
