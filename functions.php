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
	'min_spaces_proportion' => '0.01',
	'php_close_tag' => '?>',
	'big_file_size' => '1048576',
	'strange_symbols' => array(
		'0' => array (
			'name' => 'at',
			'value' => '@',
			'dang_qty' => '3'
		),
		'1' => array (
			'name' => 'dollar',
			'value' => '$',
			'dang_qty' => '8'
		),
		'2' => array (
			'name' => 'brace_curly_left',
			'value' => '{',
			'dang_qty' => '8'
		),
		'3' => array (
			'name' => 'brace_curly_right',
			'value' => '}',
			'dang_qty' => '8'
		),
		'4' => array (
			'name' => 'bracket_square_left',
			'value' => '[',
			'dang_qty' => '10'
		),
		'5' => array (
			'name' => 'bracket_square_right',
			'value' => ']',
			'dang_qty' => '10'
		),
	)
);

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

function check_js_file($filename)
{
	global $config;
	global $hashes;

	$file_contents = file($filename);
	$file_contents_string = file_get_contents($filename);
	$new_file_contents = "";
	$hash = md5(trim($file_contents_string));
	$hashes[$hash][] = $filename;

	$dangerous_functions = array(
		'charAt',
		'charCodeAt',
		'fromCharCode',
		'document'
	);

	if (false !== strpos($file_contents_string, "php"))
		write_detection ("php_in_js.txt", $filename);

	$lines_qty = count($file_contents);

	write_detection ("js_heads.txt", $filename);
	write_detection ("js_heads.txt", substr($file_contents[0], 0, 60) . " ... " . substr($file_contents[0], -60));
	write_detection ("js_heads.txt", "\n");

	write_detection ("js_tails.txt", $filename);
	write_detection ("js_tails.txt", substr($file_contents[$lines_qty - 1], 0, 60) . " ... " . substr($file_contents[$lines_qty - 1], -60));
	write_detection ("js_tails.txt", "\n");

	/* === Type 1 detect (bad functions) === */


	$line = $file_contents[$lines_qty - 1];

	$pos = strrpos($line, "function");
	if (isset($line[$pos - 1]) && ("(" == $line[$pos - 1]))
		$pos = $pos - 1;
	$last_function = substr($line, $pos);

	if ((false !== strpos($last_function, "eval")) && (false !== strpos($last_function, "charAt")))
	{
		$line = substr($line, 0, $pos);
		write_detection ("js_injects_1.txt", $filename);
		write_detection ("js_injects_1.txt", $last_function);
		write_detection ("js_injects_1.txt", "\n");
	}

	/* === end: Type 1 detect === */

	/* === Type 2 detect (bad variables) === */

	$pos = strrpos($line, "var", -(strlen($line) - $pos));
	$last_var = substr($line, $pos);

	if (false !== strpos($last_var, "\\x"))
	{
		$line = substr($line, 0, $pos);
		write_detection ("js_injects_1.txt", $filename);
		write_detection ("js_injects_1.txt", $last_var);
		write_detection ("js_injects_1.txt", "\n");
	}

	/* === end: Type 2 detect === */

	/* === Type 3 detect === */

	$delimiters = array(
		';',
		'}',
		')',
		'/'
	);

	$points = 0;
	//$line_arr = explode(";", $line);
	$line_arr = array();
	$line_split = str_split($line);
	$i = 0;
	for ($j = 0; $j < count($line_split); $j++)
	{
		if (!isset($line_arr[$i]))
			$line_arr[$i] = "";
		$line_arr[$i] .= $line_split[$j];
		if (in_array($line_split[$j], $delimiters))
			$i++;
	}

	$el_last = count($line_arr) - 1;
	$el_first = count($line_arr) - 65;

	if ($el_first >= 0)
	{
		for ($i = $el_first; $i <= $el_last; $i++)
			foreach ($dangerous_functions as $fn)
			if (false !== strpos($line_arr[$i], $fn))
				$points++;
		if ($points > 4)
		{
			$remove = "";
			for($i = $el_first; $i <= $el_last; $i++)
			{
				$remove .= $line_arr[$i];
				unset($line_arr[$i]);
			}

			$line = implode("", $line_arr);

			write_detection ("js_injects_3.txt", $filename);
			write_detection ("js_injects_3.txt", $remove);
			write_detection ("js_injects_3.txt", "\n");
		}
	}



	/* === end: Type 3 detect === */


	$file_contents[$lines_qty - 1] = $line;
	foreach($file_contents as $k => $v)
		$new_file_contents .= $v;


	if ($new_file_contents != $file_contents_string)
		update_file($filename, $new_file_contents);

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

function check_php_file($filename, $patterns, $exceptions)
{
	global $config;
	global $hashes;

	$file_contents = file($filename);
	$file_contents_string = file_get_contents($filename);
	$new_file_contents = "";

	$lines_qty = count($file_contents);

	$hash = md5(trim($file_contents_string));
	$hashes[$hash][] = $filename;

	if (($lines_qty == 1) || (($lines_qty == 2) && ($config['php_close_tag'] == $file_contents[1])))
	{
		$line = $file_contents[0];
		if (false !== strpos($line, "eval"))
		{
			write_detection ("oneliners.txt", $filename);
			$line_cut = substr($line, 0, 50) . " ... " . substr($line, -50, 50);
			write_detection ("oneliners.txt", $line_cut);
			backup_infected($filename);

			write_file_del($filename);
			unlink($filename);

			/* removing file contents to avoid further checking */
			$file_contents = array();
			$file_contents_string = "";
			$new_file_contents = "";

			write_detection ("oneliners.txt", "\n");
		}
	}

	if ($lines_qty < 3)
	{
		write_detection("short_scripts.txt", $filename);
		write_detection("short_scripts.txt", $file_contents_string);
		write_detection("short_scripts.txt", "\n");
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

		if (!$if_exclude_line)
		{
			if (0 == $line_num)
			{
				if (false !== strpos($line, "eval"))
				{
					$pos1 = strpos($line, "?><?");
					$pos2 = strpos($line, "?> <?");

					if (false !== $pos1)
						$pos = $pos1;
					else if (false !== $pos2)
						$pos = $pos2;
					else
						$pos = 0;

					if ($pos)
					{
						write_detection ("head_injects.txt", $filename);

						$line_cut = substr($line, 0, 50) . " ... " . substr($line, -50, 50);
						write_detection ("head_injects.txt", $line_cut);

						$line = substr($line, $pos + 2);

						write_detection ("head_injects.txt", "\n");
					}
				}
			}

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

	if ($new_file_contents != $file_contents_string)
		update_file($filename, $new_file_contents);

	return 1;
}


?>
