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
	$check_pattern = $argv[2];
else
	$check_pattern = "";

if (isset($check_pattern) && $config['debug'])
	echo ("check pattern: ".$check_pattern."\n");

$hashes = array();

$files_qty = count($files);
foreach($files as $file_idx => $filename)
{
	$hash = md5(trim(file_get_contents($filename)));
	$hashes[$hash][] = $filename;

	$pinfo = pathinfo($filename);
	if (isset($pinfo['extension']) && ("php" == $pinfo['extension']))
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

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
	}
	else
	{

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
			//backup_infected($file);
			write_detection("duplicates.txt", $file);
			//write_file_del($file);
			//echo ("deleting ".$file."\n");
			//unlink ($file);
		}

		write_detection("duplicates.txt", "\n");
		echo ("\n");
	}
}

?>
