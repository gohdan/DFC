#!/bin/sh

ORIGIFS=$IFS
ORIGOFS=$OFS;

IFS=$(echo -en "\n\b")
OFS=$(echo -en "\n\b")

for i in `cat $1`; do
	$EDITOR "$i" < /dev/tty
done

IFS=$ORIGIFS
OFS=$ORIGOFS

