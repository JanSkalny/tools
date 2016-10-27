#!/bin/bash

CNT=10

HOST=$1
if [ $# -ne 1 ]; then
	echo "usage: ./run_test.sh HOST" 1>&2
	exit 1
fi

mkdir -p measurements/$HOST
for TEST in aesni sw none; do
	echo -n "running test $TEST..."
	echo "" > measurements/$HOST/$TEST
	case "$TEST" in
		sw) CAPS="OPENSSL_ia32cap='~0x200000200000000'" ;;
		none) CAPS="OPENSSL_ia32cap=''" ;;
		aesni) CAPS="" ;;
	esac
	for I in `seq 1 $CNT`; do
		echo -n " $I"
		ssh $HOST "$CAPS openssl speed -evp aes-128-ecb" >> measurements/$HOST/$TEST 2>&1
	done
	echo ''
done

