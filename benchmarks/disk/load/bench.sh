#!/bin/bash

DISK=/dev/vda
BONNIE_USER=johnny
BONNIE_DISK=/tmp/test
OUTPUT="./test_"

WAIT=1
REPEAT=5

flush_cache () {
	echo -n "flushing cache in: " 
	for i in `seq $WAIT -1 1`; do
		echo -n "$i "
		sleep 1
	done
	echo -n "0... "
	ssh holly -l johnny "sudo sh -c 'echo 3 > /proc/sys/vm/drop_caches'"
	echo 3 > /proc/sys/vm/drop_caches
	echo "flushed!"
}

if [ $# -eq 0 ]; then

	echo "missing argument (test number)"
	exit 1
fi

case $1 in 
3)
	TEST=bonnie
	;;
2)
	TEST=iops
	;;
1)
	TEST=hdparm
	;;
esac

OUT="${OUTPUT}_${TEST}.txt"
echo "" > $OUT

for ITER in `seq 1 $REPEAT`; do
	echo "testing with $TEST ($ITER of $REPEAT)"
	flush_cache

	case $TEST in
	bonnie)
		mkdir -p $BONNIE_DISK
		chown $BONNIE_USER $BONNIE_DISK
		bonnie++ -d $BONNIE_DISK -u $BONNIE_USER >> $OUT
		;;

	iops)
		./iops $DISK >> $OUT
		;;

	hdparm)	
		hdparm -t --direct $DISK >> $OUT
		;;
	esac
done

