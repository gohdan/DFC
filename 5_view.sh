#!/bin/sh

for i in `cat $1`; do
	$EDITOR $i < /dev/tty
done

