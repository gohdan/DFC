#!/bin/sh

HOST=ftp.example.org
USER=example
PASS=example
RDIR=www

FTPURL="ftp://$USER:$PASS@$HOST"

lftp -c "set ftp:list-options -a;
open '$FTPURL';
cd '$RDIR';
mirror -e --verbose
bye"
