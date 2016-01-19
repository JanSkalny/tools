#!/bin/bash

#for HOST in intranet.verdesro.sk mail.verdesro.sk host1.verdesro.sk video.verdesro.sk gatekeeper.verdesro.sk backup.verdesro.sk dev.verdesro.sk; do
#for HOST in gw1.szsbaza.sk gw2.szsbaza.sk webhosting.szsbaza.sk monitor.szsbaza.sk radius.szsbaza.sk dc.szsbaza.sk holly.szsbaza.sk; do
for HOST in data.cerise.io service.cerise.io www.cerise.io true.binarity.cz bitwise.binarity.cz alfa.sportbetmedia.info ; do
	echo "$HOST..."
	ssh $HOST "sudo chmod a+r /etc/init.d/fw.sh"
	scp $HOST:/etc/init.d/fw.sh $HOST
done
