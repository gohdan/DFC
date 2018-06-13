<?php

function check_php_files($files)
{
	global $config;

	$patterns_files = scandir($config['patterns_dir']);
	$patterns = array();
	foreach ($patterns_files as $pattern_file)
		if ("." != $pattern_file && ".." != $pattern_file)
		{
			$pattern_array = parse_ini_file($config['patterns_dir']."/".$pattern_file);
			if (("" == $config['check_pattern']) || ($config['check_pattern'] == $pattern_array['name']))
				$patterns[] = $pattern_array;
		}

	$exceptions_files = scandir($config['exceptions_dir']);
	$exceptions = array();
	foreach ($exceptions_files as $exception_file)
		if ("." != $exception_file && ".." != $exception_file)
		{
			$exception_array = parse_ini_file($config['exceptions_dir']."/".$exception_file);
			if (("" == $config['check_pattern']) || ($config['check_pattern'] == $exception_array['category']))
				$exceptions[$exception_array['category']][] = $exception_array;
		}

	$files_qty = count($files);
	foreach($files as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);
		if (!check_hash($file_contents_string, $filename))
			check_php_file($filename, $file_contents_string, $patterns, $exceptions);
	}
	return 1;
}

function detect_oneliner($filename, $file_contents)
{
	global $config;

	$result = 0;

	$lines_qty = count($file_contents);

	if ( ($lines_qty == 1) ||
		( ($lines_qty == 2) && ($config['php_close_tag'] == $file_contents[1]) ) ||
		( ($lines_qty == 3) && ("" == trim($file_contents[1])) && ($config['php_close_tag'] == $file_contents[2]) )
		)
	{
		$line = $file_contents[0];
		if ( (false !== strpos($line, "eval")) || (false !== strpos($line, "sys_get_temp_dir")) )
		{
			$result = 1;
			write_detection ("oneliners.txt", $filename);
			if (strlen($line) <= 100)
				$line_cut = $line;
			else
				$line_cut = substr($line, 0, 50) . " ... " . substr($line, -50, 50);
			write_detection ("oneliners.txt", $line_cut);
			backup_infected($filename);

			write_file_del($filename);
			unlink($filename);

			write_detection ("oneliners.txt", "\n");
		}
	}

	return ($result);
}

function detect_head_inject($filename, $line)
{
	global $config;

	if ($config['debug'])
		echo ("*** head injections detection ***\n");

	foreach($config['inject_strings'] as $inject_string)
	{
		if (false !== strpos($line, $inject_string))
		{
			if ($config['debug'])
				echo ("injection detected: ".$inject_string."\n");
			$pos1 = strpos($line, "?><?");
			$pos2 = strpos($line, "?> <?");

			if (false !== $pos1)
				$pos = $pos1 + 2;
			else if (false !== $pos2)
				$pos = $pos2 + 3;
			else
				$pos = strlen($line);

			if ($pos)
			{
				write_detection ("head_injects.txt", $filename);

				if (strlen($line) > 100)	
					$line_cut = substr($line, 0, 50) . " ... " . substr($line, -50, 50);
				else
					$line_cut = $line;
				write_detection ("head_injects.txt", $line_cut);

				$line = substr($line, $pos);

				write_detection ("head_injects.txt", "\n");
			}
		}
	}

	if ($config['debug'])
		echo ("*** end: head injections detection ***\n");

	return $line;
}

function check_php_file($filename, $file_contents_string, $patterns, $exceptions)
{
	global $config;

	$new_file_contents = "";
	$file_contents = file($filename);
	$lines_qty = count($file_contents);

	if (detect_oneliner($filename, $file_contents))
	{
		/* remove file contents to avoid further checking */
		$file_contents = array();
		$file_contents_string = "";
		$new_file_contents = "";
	}

	foreach($file_contents as $line_num => $line)
	{
		if ($config['debug'])
			echo ("line ".$line_num."\n");

		$if_exclude_line = 0;

		// Search for "cache start" pattern
		if (isset($if_exclude_block) && ("1" == $if_exclude_block))
		{
			$if_exclude_line = 1;
			if (false !== strpos($line, "//###=CACHE END=###"))
				$if_exclude_block = 0;
		}
		if (false !== strpos($line, "//###=CACHE START=###"))
		{
			if ($config['debug'])
				echo ("'cache start' block, skipping\n");
			write_detection ("cache_masquerade.txt", $filename);
			$if_exclude_line = 1;
			$if_exclude_block = 1;
		}

		if (!$if_exclude_line)
		{
			if ((0 == $line_num) || (1 == $line_num))
			{
				$line_old = "";
				while ($line_old != $line)
				{
					$line_old = $line;
					$line = detect_head_inject($filename, $line);
				}
			}
		}

		// Search of long lines and base64 blocks
		if (!$if_exclude_line && (strlen($line) >= $config['dangerous_strlen']))
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
					if ($config['debug'])
						echo ("match ".$match_idx."\n");
					$mlen = strlen($match);
					if ($mlen > $config['dangerous_strlen'])
					{
						if ($config['debug'])
							echo ("string part is greater than dangerous strlen\n");
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
					}
				}
			}
		}

		// Search for cookie code execution
		if (!$if_exclude_line && ($line_num < 10) && (substr_count($line, "COOKIE") > 2))
		{
			if ($config['debug'])
				echo ("Cookie code execution\n");
			write_detection ("cookie_execution.txt", $filename);
			if (strlen($line) < 100)
				$line_cut = $line;
			else
				$line_cut = ($line_num + 1) . ": " . substr($line, 0, 50) . " ... " . substr($line, -50, 50);
			write_detection ("cookie_execution.txt", $line_cut);
			$if_exclude_line = 1;
		}

		if (!$if_exclude_line)
		{
			if (strlen($line) >= $config['dangerous_strlen'])
				write_detection_full($config['detections_dir'], $filename, $file_contents, $line_num, "long", "lines");


			/* Check for strange symbols */

			foreach($config['strange_symbols'] as $symbol_idx => $symbol)
			{
				$symbols_qty = substr_count($line, $symbol['value']);
				if ($symbols_qty > $symbol['dang_qty'])
				{
					write_detection ("symbols_". $symbol['name'] .".txt", $filename);
					write_detection ("symbols_". $symbol['name'] .".txt", $line);
					write_detection ("symbols_". $symbol['name'] .".txt", "\n");
				}
			}

			/* end: Check for strange symbols */


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

	if (($lines_qty <= 3) && ("" != $new_file_contents))
	{
		write_detection("short_scripts.txt", $filename);
		write_detection("short_scripts.txt", $new_file_contents);
		write_detection("short_scripts.txt", "\n");
	}

	if ($new_file_contents != $file_contents_string)
		update_file($filename, $new_file_contents);

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
			
			$exc_array = array();
			$exc_arr = explode(",", $exception['value']);
			foreach($exc_arr as $exc_sub)
				$exc_array[] = strtolower(trim($exc_sub, "'"));

			if ($config['debug'])
				print_r($exc_array);

			foreach($exc_array as $exc)
			{
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
					if ($config['debug'])
						echo ("it's exception\n\n");
					$if_exception = 1;
					break;
				}
				else
					if ($config['debug'])
						echo ("not exception\n\n");
			}

			if ($if_exception)
				break;
		}
	}

	if ($config['debug'] && $if_exception)
		echo ("exception\n");

	return $if_exception;
}




?>
