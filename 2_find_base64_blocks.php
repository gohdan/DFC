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

		$bad_lines = array();

		foreach($file_contents as $line_num => $line)
		{
			$len = strlen($line);
			if ($len > $config['dangerous_strlen'])
			{
				$pattern="/\"([a-zA-Z0-9=+]+)\"/i";
		        if (preg_match_all($pattern, $line, $matches))
				{
					foreach ($matches[1] as $match_idx => $match)
					{
						$mlen = strlen($match);
						if ($mlen > $config['dangerous_strlen'])
						{
							$spaces_qty = substr_count(trim($line), " ");
							$proportion = $spaces_qty / $mlen;
							if ($proportion < $config['min_spaces_proportion'])
							$bad_lines[] = $line_num;
						}
					}
				}

			}

		}

		if (count($bad_lines))
		{
			write_detection ("base64_blocks.txt", $filename);

			foreach($bad_lines as $line_num)
			{
				$line_cut = ($line_num + 1) . ": " . substr($file_contents[$line_num], 0, 50) . " ... " . substr($file_contents[$line_num], -50, 50);
				write_detection ("base64_blocks.txt", $line_cut);
			}

			$new_file_contents = "";
			foreach($file_contents as $line_num => $line)
				if (!in_array($line_num, $bad_lines))
					$new_file_contents .= $line;

			backup_infected($filename);

			if (strlen(trim($new_file_contents)) == 0)
			{
				write_detection ("base64_blocks.txt", "file becomes empty, delete");
				write_file_del($filename);
				unlink($filename);
			}
			else
			{
				write_file_repl($filename);
				file_put_contents($filename, $new_file_contents);
			}

			write_detection ("base64_blocks.txt", "\n");
		}
	}


}


?>
