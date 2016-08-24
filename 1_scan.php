<?php

include_once("functions.php");

if (isset($argv[1]))
	$scan_target = $argv[1];
else
{
	echo ("Please give file or directory to scan\n");
	exit;
}

switch(filetype($scan_target))
{
	default: break;

	case "file":
		$files = array($scan_target);
	break;

	case "dir":
		echo ("building files list...\n");
		$files = get_files_list($scan_target, array());
	break;
}

if (isset($argv[2]))
	$check_filetype = $argv[2];
else
	$check_filetype = "";

if (isset($argv[3]))
	$check_pattern = $argv[3];
else
	$check_pattern = "";

if (isset($check_pattern) && $config['debug'])
	echo ("check pattern: ".$check_pattern."\n");

global $hashes;
$hashes = array();

$files_php = array();
$files_htaccess = array();
$files_js = array();
$files_jpg = array();
$files_png = array();
$files_gif = array();
$files_bmp = array();
$files_other = array();


foreach($files as $file_idx => $filename)
{
	$memory_limit = round(0.9 * get_memory_limit());
	$filesize = filesize($filename);
	if ($filesize > $memory_limit || $filesize > $config['big_file_size'])
		write_detection ("files_big.txt", $filename."\n");
	else if (0 == $filesize)
		write_detection ("files_empty.txt", $filename."\n");
	else
	{
		$pinfo = pathinfo($filename);
		if (isset($pinfo['extension']))
			switch (strtolower($pinfo['extension']))
			{
				default: 
					$files_other[] = $filename;
				break;

				case "php":
					$files_php[] = $filename;
				break;

				case "htaccess":
					$files_htaccess[] = $filename;
				break;

				case "js":
					$files_js[] = $filename;
				break;

				case "jpg":
					$files_jpg[] = $filename;
				break;

				case "jpeg":
					$files_jpg[] = $filename;
				break;

				case "png":
					$files_png[] = $filename;
				break;

				case "bmp":
					$files_bmp[] = $filename;
				break;

				case "gif":
					$files_gif[] = $filename;
				break;

			}

		else
			$files_other[] = $filename;
	}
}

if ("" == $check_filetype || "php" == $check_filetype)
{
	echo ("scanning PHP files\n");

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

	$files_qty = count($files_php);
	foreach($files_php as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");
		check_php_file($filename, $patterns, $exceptions);
	}
}

if ("" == $check_filetype || "htaccess" == $check_filetype)
{
	echo ("scanning htaccess files\n");

	$files_qty = count($files_htaccess);
	foreach($files_htaccess as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);
		$hash = md5(trim($file_contents_string));
		$hashes[$hash][] = $filename;

		$pos = stripos($file_contents_string, "AddHandler");
		if (false !== $pos)
		{
			$begin = $pos - 20;
			if ($begin < 0)
				$begin = 0;
			write_detection ("htaccess_addhandler.txt", $filename);
			write_detection ("htaccess_addhandler.txt", substr($file_contents_string, $begin, 20));
			write_detection ("htaccess_addhandler.txt", "\n");
		}
	}
}


if ("" == $check_filetype || "js" == $check_filetype)
{
	echo ("scanning JS files\n");

	$files_qty = count($files_js);
	foreach($files_js as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		if (isset($check_pattern))
		{
			switch($check_pattern)
			{
				default:
					check_js_file($filename);
				break;

				case "remove_last_line":
					remove_last_line($filename);
				break;
			}
		}
		else
			check_js_file($filename);
	}
}

if ("" == $check_filetype || "jpg" == $check_filetype)
{
	echo ("scanning jpg files\n");

	$files_qty = count($files_jpg);
	foreach($files_jpg as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);
		$hash = md5(trim($file_contents_string));
		$hashes[$hash][] = $filename;

		$pos = stripos($file_contents_string, "php");
		if (false !== $pos)
		{
			$begin = $pos - 10;
			if ($begin < 0)
				$begin = 0;
			write_detection ("php_in_jpg.txt", $filename);
			write_detection ("php_in_jpg.txt", substr($file_contents_string, $begin, 20));
			write_detection ("php_in_jpg.txt", "\n");
		}
	}
}

if ("" == $check_filetype || "png" == $check_filetype)
{
	echo ("scanning png files\n");

	$files_qty = count($files_png);
	foreach($files_png as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);
		$hash = md5(trim($file_contents_string));
		$hashes[$hash][] = $filename;

		$pos = stripos($file_contents_string, "php");
		if (false !== $pos)
		{
			$begin = $pos - 10;
			if ($begin < 0)
				$begin = 0;
			write_detection ("php_in_png.txt", $filename);
			write_detection ("php_in_png.txt", substr($file_contents_string, $begin, 20));
			write_detection ("php_in_png.txt", "\n");
		}
	}
}

if ("" == $check_filetype || "gif" == $check_filetype)
{
	echo ("scanning gif files\n");

	$files_qty = count($files_gif);
	foreach($files_gif as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);
		$hash = md5(trim($file_contents_string));
		$hashes[$hash][] = $filename;

		$pos = stripos($file_contents_string, "php");
		if (false !== $pos)
		{
			$begin = $pos - 10;
			if ($begin < 0)
				$begin = 0;
			write_detection ("php_in_gif.txt", $filename);
			write_detection ("php_in_gif.txt", substr($file_contents_string, $begin, 20));
			write_detection ("php_in_gif.txt", "\n");
		}
	}
}

if ("" == $check_filetype || "bmp" == $check_filetype)
{
	echo ("scanning bmp files\n");

	$files_qty = count($files_bmp);
	foreach($files_bmp as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);
		$hash = md5(trim($file_contents_string));
		$hashes[$hash][] = $filename;

		$pos = stripos($file_contents_string, "php");
		if (false !== $pos)
		{
			$begin = $pos - 10;
			if ($begin < 0)
				$begin = 0;
			write_detection ("php_in_bmp.txt", $filename);
			write_detection ("php_in_bmp.txt", substr($file_contents_string, $begin, 20));
			write_detection ("php_in_bmp.txt", "\n");
		}
	}
}

if ("" == $check_filetype || "other" == $check_filetype)
{
	echo ("scanning other files\n");

	$files_qty = count($files_other);
	foreach($files_other as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);
		$hash = md5(trim($file_contents_string));
		$hashes[$hash][] = $filename;

		$pos = stripos($file_contents_string, "php");
		if (false !== $pos)
		{
			$begin = $pos - 10;
			if ($begin < 0)
				$begin = 0;
			write_detection ("php_in_otherfiles.txt", $filename);
			write_detection ("php_in_otherfiles.txt", substr($file_contents_string, $begin, 20));
			write_detection ("php_in_otherfiles.txt", "\n");
		}
	}
}


foreach($hashes as $hash => $files)
{
	if (count($files) > 1)
	{
		foreach ($files as $file)
		{
			if ($config['debug'])
				echo ("duplicate ".$file."\n");
			write_detection("files_duplicate.txt", $file);
		}

		write_detection("files_duplicate.txt", "\n");
	}
}

?>
