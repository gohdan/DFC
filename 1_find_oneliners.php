<?php

include_once("functions.php");

if ($argc < 2)
	echo ("Please give suspictions file name\n");
else
{
	$suspictions = file($argv[1], FILE_IGNORE_NEW_LINES);

	foreach($suspictions as $filename)
	{
		$file_contents = file($filename, FILE_IGNORE_NEW_LINES);

		$lines_qty = count($file_contents);

		if (($lines_qty == 1) || (($lines_qty == 2) && ("?>" == $file_contents[1])))
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

				write_detection ("oneliners.txt", "\n");
			}
		}
	}
}


?>
