<?php

function check_suspected_files($files)
{
	global $config;

	$files_qty = count($files);
	foreach($files as $file_idx => $filename)
	{
		echo (($file_idx + 1)." / ". $files_qty ." ".$filename."\n");
		write_detection ("files_suspected.txt", $filename);
	}
	return 1;
}

?>
