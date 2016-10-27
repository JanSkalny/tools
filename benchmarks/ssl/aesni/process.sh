#!/bin/bash
for HOST in `ls measurements`; do
	for TEST in `ls measurements/$HOST`; do
		grep 'aes-128-...  ' measurements/$HOST/$TEST | sed "s/.*/$HOST-$TEST &/" | sed 's/k / /g' | sed 's/k$//' | sed -E 's/[ ]+/	/g'
	done
done
