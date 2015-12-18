#!/bin/bash

# trim away aything below 80% or abow 120% of clean (non-zero based) average
X="0.8"

for TEST in `ls measurements`; do
	for DIR in rx tx; do
		for LEN in `ls measurements/$TEST/$DIR | sort -n`; do
			# calculate average, without zeros
			AVG=$( grep 'main_thread.*[0-9]* pps' measurements/$TEST/$DIR/$LEN | awk '
			{
				if($4>0) {
					cnt++;
					total += $4;
				} else {
					ign++;
				}
			} END {
				printf("%d", 
					cnt ? total/cnt : 0);
			}' )

			# trim anything below 0.8 AVG
			grep 'main_thread.*[0-9]* pps' measurements/$TEST/$DIR/$LEN | awk '
			{
				if ($4 >= '$X'*'$AVG' && $4 <= (2-'$X')*'$AVG') {
					cnt++;
					total += $4;
				} else {
					ign++;
				}
			} END {
				printf("'$TEST'\t'$DIR'\t'$LEN'\t%d\t%.2f\n", 
					cnt ? total/cnt : 0, 
					(cnt+ign) ? ign/(cnt+ign) : 1.0);
			}'
		done
	done
done
