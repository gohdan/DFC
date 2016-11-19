<?php

if ($argc < 2)
	echo ("Please give report file name to parse\n");
else
{
	$report = $argv[1];

	$reportfile = file_get_contents($report);

	$dom = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	$dom->loadHTML($reportfile);
    $domxpath = new DOMXPath($dom);

    $filtered = $domxpath->query("//a[@class='it']");

    $i = 0;
    while($item = $filtered->item($i++)->nodeValue)
		echo ($item."\n");

    $filtered = $domxpath->query("//div[@class='warn']");

    $i = 0;
    while($item = $filtered->item($i++)->nodeValue)
		echo ($item."\n");

}

?>
