#!/bin/sh

find $1 -type f | grep -v "\.php" | grep -v "\.js" | grep -v "\.css" | grep -v "\.scss" | grep -v "\.po" | grep -v "\.mo" | grep -v "\.sql" | grep -v "\.yml" | grep -v "\.txt" | grep -v "\.md" | grep -v "\.xml" | grep -v "\.xsl" | grep -v "\.jpg" | grep -v "\.png" | grep -v "\.gif" | grep -v "\.svg" | grep -v "\.mp3" | grep -v "\.pdf" | grep -v "\.ttf" | grep -v "\.swf"
