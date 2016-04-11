<?php

$slash = "/";
$vir_folder = "virii";
$files_del_file = "files_del.txt";
$files_repl_file = "files_repl.txt";
$files_del = array();
$files_repl = array();

function write_to_file($file, $data)
{
	if ($f = fopen($file, 'a'))
	{
		if (is_writable($file))
		{
			foreach($data as $k => $v)
				fwrite($f, $v."\n");
		}
		else
			echo ("can't write to ".$file."\n");
		fclose($f);
	}
	else
		echo ("can't open ".$file." for writing\n");

}

if ($argc < 2)
	echo ("Please give suspicions file name\n");
else
{
	$suspictions = file($argv[1], FILE_IGNORE_NEW_LINES);

	$basedir = __DIR__.$slash;
	echo ("basedir: ".$basedir."\n");

	foreach($suspictions as $s_idx => $file)
	{
		echo ($file.": ");
		$file = str_replace($basedir, "", $file);
		$path_arr = explode($slash, $file);

		$filename = array_pop($path_arr);

		$path = "";
		foreach($path_arr as $k => $v)
		{
			if (0 == $k)
				$v = $vir_folder;

			$path .= $v.$slash;
		}
		$file_vir = $path.$slash.$filename;

		$size = filesize($file);
		$size_vir = filesize($file_vir);

		echo ($size." / ".$size_vir.", ");

		if ($size != $size_vir)
		{
			if ("0" == $size || "1" == $size)
			{
				echo ("delete\n");
				$files_del[] = $file;
				unlink($file);
			}
			else
			{
				echo ("replace\n");
				$files_repl[] = $file;
			}
		}
		else
		{
			echo ("no virus found, removing backup\n");
			unlink($file_vir);
		}
	}

	if (count($files_del))
		write_to_file($files_del_file, $files_del);
	if (count($files_repl))
		write_to_file($files_repl_file, $files_repl);
}


?>
