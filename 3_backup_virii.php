<?php

$slash = "/";
$vir_folder = "virii";

if ($argc < 2)
	echo ("Please give suspictions file name\n");
else
{
	$suspictions = file($argv[1], FILE_IGNORE_NEW_LINES);

	$basedir = __DIR__.$slash;

	foreach($suspictions as $s_idx => $s_file)
	{
		$file = str_replace ($basedir, "", $s_file);
		echo ($file."\n");
		$path_arr = explode($slash, $file);

		$filename = array_pop($path_arr);

		$path = "";
		foreach($path_arr as $k => $v)
		{
			if (0 == $k)
				$v = $vir_folder;

			$path .= $v.$slash;

			if (!file_exists($path))
				mkdir($path);
		}

		$s_file_new = $path.$slash.$filename;
		if (!file_exists($s_file_new))
			copy ($s_file, $s_file_new);
	}

}


?>
