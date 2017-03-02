<?php

include_once("includes/config.php");
include_once("includes/functions.php");

if (isset($argv[1]))
	$config['scan_target'] = $argv[1];
else
{
	echo ("Please give file or directory to scan\n");
	exit;
}

switch(filetype($config['scan_target']))
{
	default: break;

	case "file":
		$files = array($config['scan_target']);
	break;

	case "dir":
		echo ("building files list...\n");
		$files = get_files_list($config['scan_target'], array());
	break;
}

if (isset($argv[2]))
	$check_filetype = $argv[2];
else
	$check_filetype = "";

if (isset($argv[3]))
{
	$config['check_pattern'] = $argv[3];
	if ($config['debug'])
		echo ("check pattern: ".$check_pattern."\n");
}


global $hashes;
$hashes = array();

$files_by_type = array();

foreach($files as $file_idx => $filename)
{
	$memory_limit = round(0.9 * get_memory_limit());
	$filesize = filesize($filename);
	if ($filesize > $memory_limit || $filesize > $config['big_file_size'])
		write_detection ("files_big.txt", $filename."\n");
	else if (0 == $filesize)
		write_detection ("files_empty.txt", $filename."\n");
	else
	{
		$pinfo = pathinfo($filename);
		if (isset($pinfo['extension'])) 
		{
			$if_has_group = 0;
			foreach ($config['filetypes'] as $filetype_group => $filetypes)
			{
				if (in_array(strtolower($pinfo['extension']), $filetypes))
					$files_by_group[$filetype_group][] = $filename;
				$if_has_group = 1;
			}
			if (!$if_has_group)
				$files_by_group['other'][] = $filename;
		}
		else
			$files_by_group['other'][] = $filename;
	}
}

foreach ($files_by_group as $filetype_group_name => $filetype_group)
{
	if ("" == $check_filetype || $filetype_group_name == $check_filetype)
	{
		echo ("scanning file type: ".$filetype_group_name."\n");
		include("includes/functions_".$filetype_group_name.".php");
		$fn_name = "check_".$filetype_group_name."_files";
		$fn_name($filetype_group);
	}


}

foreach($hashes as $hash => $files)
{
	if (count($files) > 1)
	{
		foreach ($files as $file)
		{
			if ($config['debug'])
				echo ("duplicate ".$file."\n");
			write_detection("files_duplicate.txt", $file);
		}

		write_detection("files_duplicate.txt", "\n");
	}
}

?>
