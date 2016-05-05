<?php

include_once("functions.php");

if ($argc < 2)
	echo ("Please give suspictions file name\n");
else
{
	$suspictions = file($argv[1], FILE_IGNORE_NEW_LINES);

	$hashes = array();

	foreach($suspictions as $file)
	{
		$hash = md5(trim(file_get_contents($file)));
		$hashes[$hash][] = $file;

	}

	foreach($hashes as $hash => $files)
	{
		if (count($files) > 1)
		{
			foreach ($files as $file)
			{
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

}


?>
