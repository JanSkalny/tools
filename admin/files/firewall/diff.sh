#!/bin/bash

# prepare and clean-up live/ directory
mkdir -p live/
rm -f live/*

# download and compare
for HOST in $( cat ../../hosts  | grep '^[a-z]' | grep -v 'ansible_ssh' ); do
	echo "comparing $HOST..."
	ssh $HOST "sudo chmod a+r /etc/init.d/fw.sh"
	scp $HOST:/etc/init.d/fw.sh live/$HOST > /dev/null
	diff -u $HOST live/$HOST
done
