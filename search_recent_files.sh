#!/bin/sh

find . -printf "%T@ %Tc %p\n" | sort -n
