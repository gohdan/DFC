<?php

function check_js_files($files)
{
	global $config;

	$files_qty = count($files);
	foreach($files as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);
		$if_check_file = !check_hash($file_contents_string, $filename);

		if (isset($check_pattern))
		{
			switch($check_pattern)
			{
				default:
					if ($if_check_file)
						check_js_file($filename, $file_contents_string);
				break;

				case "remove_last_line":
					remove_last_line($filename);
				break;
			}
		}
		else
			if ($if_check_file)
				check_js_file($filename, $file_contents_string);
	}
	return 1;
}

function check_js_file($filename, $file_contents_string)
{
	global $config;

	$file_contents = file($filename);
	$new_file_contents = "";

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

?>
