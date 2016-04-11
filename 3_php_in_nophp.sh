#!/bin/sh

#grep -RHni --exclude="*.php" -e "php\|<?" $1
grep -RHni --exclude="*.php" -e "<?php" $1
