#!/bin/bash

test -z "$1" && exit 1
test -z "$2" && exit 2

/bin/cat "$1" | {
	/usr/local/bin/stl2json -f
} | sed 's/,"attributeByteCount":0//g' | gzip -c > "$2"

exit 0
