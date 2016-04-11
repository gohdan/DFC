#!/bin/sh

threshold=400

let lines=`wc -L "$1" | awk {'print $1'}`

if [[ $lines -ge $threshold ]]
then
	echo "===================================="
	echo 
	echo $lines $1
	head -n 5 $1
	echo
	echo "************************************"
	echo
	tail -n 5 $1
	echo
	echo
	echo
fi

