#!/bin/bash

# $1 - index.html.bak.bak to remove .bak.bak, remove corresponding index.php, restore original .html

if [[ '' != $1 ]]
then
    echo $1
	fname=`echo $1 | awk -F .html.bak.bak '{print $1}'`
	echo $fname
	
	echo $fname.html >> files_repl.txt
	echo $fname.html.bak.bak >> files_del.txt
	echo $fname.php >> files_del.txt
	
	mv $fname.html.bak.bak $fname.html
	rm $fname.php

else
	echo "Please give file name to debak"
fi


