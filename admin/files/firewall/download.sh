#!/bin/bash

# download live versions
for HOST in $( cat ../../hosts  | grep '^[a-z]' | grep -v 'ansible_ssh' ); do
	echo "downloading $HOST..."
	ssh $HOST "sudo chmod a+r /etc/init.d/fw.sh"
	scp $HOST:/etc/init.d/fw.sh $HOST
done
