#!/bin/bash

function check_domain {
	local DOMAIN=$1

	echo -n "checking $DOMAIN... "

	if [[ $DOMAIN =~ \.(sk)$ ]]; then
		whois $DOMAIN | grep 'Not found.'
		if [ $? -ne 0 ]; then echo "taken" ; fi
	elif [[ $DOMAIN =~ \.(org)$ ]]; then
		whois $DOMAIN | grep 'NOT FOUND'
		if [ $? -ne 0 ]; then echo "taken" ; fi
	elif [[ $DOMAIN =~ \.(eu)$ ]]; then
		whois $DOMAIN | grep 'Status:'
		if [ $? -ne 0 ]; then echo "taken" ; fi
	elif [[ $DOMAIN =~ \.(com|net)$ ]]; then
		whois $DOMAIN | grep 'No match for'
		if [ $? -ne 0 ]; then echo "taken" ; fi
	fi
}

while read -p "domain name: " DOMAIN; do
	check_domain "$DOMAIN".com
	check_domain "$DOMAIN".eu
	check_domain "$DOMAIN".sk
	check_domain "$DOMAIN".net
	check_domain "$DOMAIN".org
done

