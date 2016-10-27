#!/bin/bash

CNT=10

HOST=$1
if [ $# -ne 1 ]; then
	echo "usage: ./run_test.sh HOST" 1>&2
	exit 1
fi

mkdir -p measurements/$HOST
for M in 1 2 3 4 6 8; do
	echo -n "running mutli test ($M)..."
	echo "" > measurements/$HOST/$M
	for I in `seq 1 $CNT`; do
		echo -n " $I"
		ssh $HOST "openssl speed -multi $M -evp aes-128-ecb" >> measurements/$HOST/$M 2>&1 
	done
	echo ''
done

