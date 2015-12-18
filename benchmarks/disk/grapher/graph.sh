#!/bin/bash

CNT=500
DEV="dm-7"

START=`date '+%s'`
END=$((START + CNT))

OUT="iostat_$START"
RRD=${OUT}.rrd
IMG=${OUT}.png

rrdtool create $RRD \
	--start $START \
	--step 1 \
	DS:r_mbps:GAUGE:1:0:671744 \
	DS:w_mbps:GAUGE:1:0:671744 \
	DS:req_size:GAUGE:1:0:671744 \
	DS:queue:GAUGE:1:0:671744 \
	DS:a_wait:GAUGE:1:0:671744 \
	DS:r_wait:GAUGE:1:0:671744 \
	DS:w_wait:GAUGE:1:0:671744 \
	RRA:AVERAGE:0.5:1:$CNT

NOW=$START
iostat -x -m $DEV 1 $CNT | grep $DEV | while read LINE; do
	X=( $LINE )

	R_IOPS="${X[3]}"
	W_IOPS="${X[4]}"
	R_MBPS="${X[5]}"
	W_MBPS="${X[6]}"
	REQ_SIZE="${X[7]}"
	QUEUE_LEN="${X[8]}"
	AVG_WAIT="${X[9]}"
	R_WAIT="${X[10]}"
	W_WAIT="${X[11]}"

	NOW=$((NOW + 1))

	#echo "now=$NOW queue=$QUEUE_LEN line=$LINE"
	rrdtool update $RRD $NOW:$R_MBPS:$W_MBPS:$REQ_SIZE:$QUEUE_LEN:$AVG_WAIT:$R_WAIT:$W_WAIT
done

rrdtool graph $IMG -s $START -e $END \
	--x-grid SECOND:5:SECOND:30:SECOND:30:0:%S \
	--width 1200 \
	--height 400 \
	DEF:r_mbps=$RRD:r_mbps:AVERAGE \
	DEF:w_mbps=$RRD:w_mbps:AVERAGE \
	DEF:queue=$RRD:queue:AVERAGE \
	DEF:req_size=$RRD:req_size:AVERAGE \
	DEF:a_wait=$RRD:a_wait:AVERAGE \
	LINE1:r_mbps#00FFFF:"Read [MB/s]\l" \
	LINE1:w_mbps#FF00FF:"Write [MB/s]\l" \
	LINE1:queue#0000FF:"Queue length \l" \
	LINE1:req_size#00FF00:"AVG request size \l" \
	LINE1:a_wait#FF0000:"AVG delay\l"

