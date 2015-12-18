#!/bin/bash

echo "use super_process.sh"
exit 1

for TEST in `ls measurements`; do
	for DIR in rx tx; do
		for LEN in `ls measurements/$TEST/$DIR | sort -n`; do
			echo -n -e "$TEST\t$DIR	$LEN	"
			grep 'main_thread.*[0-9]* pps' measurements/$TEST/$DIR/$LEN | sed '1d;x' | awk '{pkts+=$4; count++}END{ printf("%d\n", pkts/count); }'
		done
	done
done
