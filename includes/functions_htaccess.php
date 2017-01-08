<?php

function check_htaccess_files($files)
{
	global $config;

	$files_qty = count($files);
	foreach($files as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");

		$file_contents_string = file_get_contents($filename);

		if (!check_hash($file_contents_string, $filename))
		{
			$pos = stripos($file_contents_string, "AddHandler");
			if (false !== $pos)
			{
				$begin = $pos - 50;
				if ($begin < 0)
					$begin = 0;
				write_detection ("htaccess_addhandler.txt", $filename);
				write_detection ("htaccess_addhandler.txt", substr($file_contents_string, $begin, 100));
				write_detection ("htaccess_addhandler.txt", "\n");
			}

			$pos = stripos($file_contents_string, "AddType");
			if (false !== $pos)
			{
				$begin = $pos - 50;
				if ($begin < 0)
					$begin = 0;
				write_detection ("htaccess_addtype.txt", $filename);
				write_detection ("htaccess_addtype.txt", substr($file_contents_string, $begin, 100));
				write_detection ("htaccess_addtype.txt", "\n");
			}
		}
	}
	return 1;
}

?>
