<?php

include_once("includes/config.php");
include_once("includes/functions.php");

if ($argc < 2)
	echo ("Please give suspictions file name\n");
else
{
	$suspictions = file($argv[1], FILE_IGNORE_NEW_LINES);

	foreach($suspictions as $s_idx => $s_file)
		backup_infected($s_file);	
}


?>
