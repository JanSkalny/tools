#!/bin/bash -x

# use first modem device
MODEM=$( mmcli -L | grep 'Modem\/[0-9]' | cut -d'/' -f6 | awk '{print $1}' )
NUMBER="$1"
TEXT="$2"

# validate text - no longer then 134 bytes
if [ $# -ne 2 ]; then
	echo "Missing arguments.Usage: ./send_sms +421... \"message string\" " 1>&2
	exit 1
fi

# remove some weird characters
if [ ${#TEXT} -ge 134 ]; then
	echo "Message too long. Sending only 134 bytes." 1>&2
	TEXT=${TEXT:0:134}
fi
TEXT=$( echo "$TEXT" | sed "s#['\"\\/]##g" )

# validate phone number
if [[ ! $NUMBER =~ ^\+(421|48) ]]; then
	echo "Invalid phone number." 1>&2
	exit 2
fi

MSG_ID=$( mmcli -m $MODEM --messaging-create-sms="text='$TEXT',number='$NUMBER'" | grep 'SMS/[0-9]' | cut -d'/' -f 6 | awk '{print $1}' )
if [ $? -ne 0 ]; then
	echo "Failed to create message." 1>&2
	exit 3
fi

sleep 0.1

mmcli -m $MODEM -s $MSG_ID --send 
if [ $? -ne 0 ]; then
	echo "Failed to send message." 1>&2
	exit 4
fi
