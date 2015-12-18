#!/bin/sh

PORT=2001

exec > /dev/null 2>&1

sleep 5

ssh -tt \
	-o BatchMode=yes \
	-o ServerAliveInterval=15 \
	-o ServerAliveCountMax=3 \
	-o SetupTimeout=30 \
	-o ConnectTimeout=30 \
	-o ConnectionAttempts=1 \
	-l cat netvor.sk -R $PORT:127.0.0.1:22 
