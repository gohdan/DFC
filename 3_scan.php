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

$files_php = array();
$files_nophp = array();
foreach($files as $file_idx => $filename)
{
	$pinfo = pathinfo($filename);
	if (isset($pinfo['extension']) && ("php" == $pinfo['extension']))
		$files_php[] = $filename;
	else
		$files_nophp[] = $filename;
}

echo ("scanning PHP files\n");

$files_qty = count($files_php);
foreach($files_php as $file_idx => $filename)
{
	echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

	$file_contents = file($filename, FILE_SKIP_EMPTY_LINES);
	$file_contents_string = file_get_contents($filename);

	$hash = md5(trim($file_contents_string));
	$hashes[$hash][] = $filename;

	$new_file_contents = "";

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

		$if_exclude_line = 0;

		if (strlen($line) >= $config['dangerous_strlen'])
		{
			if ($config['debug'])
				echo ("dangerous strlen\n");

			/* Search of big base64 blocks */
			$pattern="/([a-zA-Z0-9\\\=\/+]+)/i";
	        if (preg_match_all($pattern, $line, $matches))
			{
				if ($config['debug'])
					echo ("have base64 block pattern match\n");

				foreach ($matches[1] as $match_idx => $match)
				{
					$mlen = strlen($match);
					if ($mlen > $config['dangerous_strlen'])
					{
						$spaces_qty = substr_count(trim($match), " ");
						$proportion = $spaces_qty / $mlen;
						if ($proportion < $config['min_spaces_proportion'])
						{
							if ($config['debug'])
								echo ("not enough spaces, removing line from file\n");
							write_detection ("base64_blocks.txt", $filename);
							$line_cut = ($line_num + 1) . ": " . substr($line, 0, 50) . " ... " . substr($line, -50, 50);
							write_detection ("base64_blocks.txt", $line_cut);
							$if_exclude_line = 1;
						}
						else
						{
							if ($config['debug'])
								echo ("enough spaces\n");

							write_detection_full($config['detections_dir'], $filename, $file_contents, $line_num, "long", "lines");
						}
					}
					else
						write_detection_full($config['detections_dir'], $filename, $file_contents, $line_num, "long", "lines");
				}
			}
			else
			{
				if ($config['debug'])
					echo ("no base64 block pattern match\n");
				write_detection_full($config['detections_dir'], $filename, $file_contents, $line_num, "long", "lines");
			}
		}

		if (!$if_exclude_line)
		{
			$new_file_contents .= $line;

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
		}
		else
			if ($config['debug'])
				echo ("excluding line from file\n");
	}

	if ($new_file_contents != $file_contents_string)
	{
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
				echo ("overwriting file");
			write_file_repl($filename);
			file_put_contents($filename, $new_file_contents);
		}
	}
}

echo ("scanning non-PHP files\n");

$files_qty = count($files_nophp);
foreach($files_nophp as $file_idx => $filename)
{
	echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

	$file_contents_string = file_get_contents($filename);
	$hash = md5(trim($file_contents_string));
	$hashes[$hash][] = $filename;

	if (false !== strpos($file_contents_string, "php"))
		write_detection ("php_in_nophp.txt", $filename);
}


foreach($hashes as $hash => $files)
{
	if (count($files) > 1)
	{
		foreach ($files as $file)
		{
			if ($config['debug'])
				echo ("duplicate ".$file."\n");
			write_detection("duplicates.txt", $file);
		}

		write_detection("duplicates.txt", "\n");
	}
}

?>
