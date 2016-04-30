#!/bin/sh

ORIGIFS=$IFS
ORIGOFS=$OFS;

IFS=$(echo -en "\n\b")
OFS=$(echo -en "\n\b")

echo "counting files..."

FILESLIST=/tmp/fileslist.txt

FILES=`find $1 -name "*.php" > $FILESLIST`
QTY=`wc -l $FILESLIST | awk '{print $1}'`

echo $QTY" files to check"

K=0
for i in `cat $FILESLIST`; do
	let K=$K+1
	echo $K / $QTY $i
	php -f scan.php "$i"
done

# find $1 -name "*.php" -exec php -f scan.php {} \;

if [ -e $FILESLIST ]; then
	rm $FILESLIST
fi

IFS=$ORIGIFS
OFS=$ORIGOFS

