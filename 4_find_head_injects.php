<?php

include_once("functions.php");

if ($argc < 2)
	echo ("Please give suspictions file name\n");
else
{
	$suspictions = file($argv[1], FILE_IGNORE_NEW_LINES);

	foreach($suspictions as $filename)
	{
		$file_contents = file($filename);

		$line = $file_contents[0];

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

				backup_infected($filename);

				$file_contents[0] = substr($line, $pos + 2);

				write_file_repl($filename);

				$new_file_contents = "";
				foreach($file_contents as $line_num => $line)
					$new_file_contents .= $line;
				file_put_contents($filename, $new_file_contents);

				write_detection ("head_injects.txt", "\n");
			}
		}
	}
}


?>
