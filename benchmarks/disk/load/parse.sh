#!/bin/bash

OUTPUT="./test_"

echo "hdparm"
cat ${OUTPUT}_hdparm.txt  | awk '{print $11}' | grep -v '^$'
echo ""

echo "iops-512"
cat ${OUTPUT}_iops.txt | grep '512   B' | awk '{print $4}'
echo ""

echo "iops-4k"
cat ${OUTPUT}_iops.txt | grep ' 4 KiB' | awk '{print $4}'
echo ""

echo "iops-1m"
cat ${OUTPUT}_iops.txt | grep ' 1 MiB' | awk '{print $4}'
echo ""

echo "bonnie++"
cat ${OUTPUT}_bonnie.txt | grep ','
echo ""

