#!/bin/bash

SINK=shodan
SINK_DEV=em0

GEN=disco-stu
GEN_DEV=em0

DURATION=20
WAIT=5
DURATION=$( expr $DURATION - $WAIT )

GEN_ARGS=""
#GEN_ARGS="-S 00:1b:21:13:db:d4 -D AC:22:0B:8D:4C:8A -s 1.1.1.10 -d 2.2.2.20"
COMMON_ARGS="-w $WAIT -T 100"

TEST=$1
if [ $# -ne 1 ]; then
	echo "usage: ./run_test.sh TEST_NAME" 1>&2
	exit 1
fi

mkdir -p measurements/$TEST/tx measurements/$TEST/rx > /dev/null 2>&1
echo "removing old measurements..."
rm measurements/$TEST/tx/* measurements/$TEST/rx/* > /dev/null 2>&1

for LEN in 64 128 256 512 1024 1280 1518; do
	# kill previous test (if any)
	ssh $GEN killall pkt-gen > /dev/null 2>&1
	ssh $SINK killall pkt-gen > /dev/null 2>&1
	sleep 1

	PLEN=$( expr $LEN - 4)

	# start generator 
	echo "starting generator ($LEN Bytes)"
	ssh $GEN "/usr/src/tools/tools/netmap/pkt-gen -i $GEN_DEV -f tx $GEN_ARGS -l $PLEN $COMMON_ARGS" > measurements/$TEST/tx/$LEN 2>&1 &
	GEN_PID=$!

	# start measurement 
	echo "starting sink"
	ssh $SINK "/usr/src/tools/tools/netmap/pkt-gen -i $SINK_DEV -f rx $COMMON_ARGS" > measurements/$TEST/rx/$LEN 2>&1 &
	SINK_PID=$!

	# testing in progress
	echo "test is running... (t=$DURATION)"
	sleep $DURATION

	# stop testing
	echo "stopping test..."
	ssh $GEN killall pkt-gen &
	ssh $SINK killall pkt-gen &

	wait $SINK_PID $GEN_PID
	echo "done"
	sleep 3
done
