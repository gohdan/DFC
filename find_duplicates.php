<?php

if ($argc < 2)
	echo ("Please give suspictions file name\n");
else
{
	$suspictions = file($argv[1], FILE_IGNORE_NEW_LINES);

	$hashes = array();

	foreach($suspictions as $file)
	{
		$hash = md5(file_get_contents($file));
		$hashes[$hash][] = $file;

	}

	foreach($hashes as $hash => $files)
	{
		foreach ($files as $file)
			echo ($file."\n");
		echo ("\n\n\n");
	}

}


?>
