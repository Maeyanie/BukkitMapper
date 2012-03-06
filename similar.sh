#!/bin/bash

# To generate: simhash -f 512 -w *.java

DIR=`dirname $0`

(for x in $DIR/bukkit/*.sim
	do SIM=`simhash -c $DIR/mcp/$1.java.sim $x`
	FILE=`echo $x | sed -e 's/.*\/\([^\.]\+\)\.java\.sim$/\1/'`
	echo "$SIM $FILE"
done) | sort
