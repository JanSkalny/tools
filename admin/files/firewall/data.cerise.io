#! /bin/sh

### BEGIN INIT INFO
# Provides:          fw.sh
# Required-Start:    $local_fs $network
# Required-Stop:     $local_fs
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: fw.sh
# Description:       firewall
### END INIT INFO

#
# Simple iptables management script (fw.sh)
# Jan Skalny <jan@skalny.sk>
#
# UPDATE HISTORY:
# 2014-08-23  initial fw.sh configuration
# 2016-10-27  added LSB and unified update history
#

IT=/sbin/iptables

WAN_IF=eth0

MONITOR=92.240.244.36
BACKUP=194.160.5.138

modprobe ip_conntrack
modprobe ip_conntrack_ftp

echo 1 > /proc/sys/net/ipv4/ip_forward
echo 1 > /proc/sys/net/ipv4/tcp_syncookies
echo 0 > /proc/sys/net/ipv4/conf/all/rp_filter
echo 0 > /proc/sys/net/ipv4/conf/all/log_martians
echo 1 > /proc/sys/net/ipv4/icmp_echo_ignore_broadcasts
echo 1 > /proc/sys/net/ipv4/icmp_ignore_bogus_error_responses
echo 0 > /proc/sys/net/ipv4/conf/all/send_redirects
echo 0 > /proc/sys/net/ipv4/conf/all/accept_source_route

for TABLE in filter nat mangle; do
	$IT -t $TABLE -F
	$IT -t $TABLE -X
done

$IT -P INPUT DROP
$IT -P OUTPUT ACCEPT
$IT -P FORWARD DROP


############################################################
### custom ACCEPT / REJECT / DROP actions

$IT -N LOG_REJECT
	$IT -A LOG_REJECT -m limit --limit 5/sec --limit-burst 8 -j LOG \
		--log-ip-options --log-tcp-options --log-prefix fw-reject_ --log-level debug
	$IT -A LOG_REJECT -m limit --limit 5/sec --limit-burst 8 -j REJECT \
		--reject-with icmp-port-unreachable
	$IT -A LOG_REJECT -j DROP

$IT -N LOG_DROP
	$IT -A LOG_DROP -m limit --limit 5/sec --limit-burst 8 -j LOG \
		--log-ip-options --log-tcp-options --log-prefix fw-drop_ --log-level debug
	$IT -A LOG_DROP -j DROP

$IT -N LOG_ACCEPT
	$IT -A LOG_ACCEPT -m limit --limit 10/sec --limit-burst 20 -j LOG \
		--log-ip-options --log-tcp-options --log-prefix fw-accept_ --log-level debug
	$IT -A LOG_ACCEPT -j ACCEPT


############################################################
### verify source address of packet (RFC 2827, RFC 1918)

$IT -N check_if
	$IT -A check_if -j RETURN 

	# no-one can send from special address ranges!
	$IT -A check_if -s 127.0.0.0/8 -j LOG_DROP	# loopback
	$IT -A check_if -s 0.0.0.0/8 -j DROP		# DHCP
	$IT -A check_if -s 169.254.0.0/16 -j DROP	# APIPA
	$IT -A check_if -s 192.0.2.0/24 -j LOG_DROP	# RFC 3330
	$IT -A check_if -s 204.152.64.0/23 -j LOG_DROP  # RFC 3330
	$IT -A check_if -s 224.0.0.0/3 -j DROP		# multicast
	$IT -A check_if -s 100.64.0.0/10 -j LOG_DROP	# RFC 6598

	# public interface (WAN_IF) MUST NOT containt any private address
	$IT -A check_if -i $WAN_IF -s 192.168.0.0/16 -j LOG_DROP
	$IT -A check_if -i $WAN_IF -s 172.16.0.0/12 -j LOG_DROP
	$IT -A check_if -i $WAN_IF -s 10.0.0.0/8 -j LOG_DROP
	$IT -A check_if -i $WAN_IF -j RETURN

	# TODO: firewall is disabled!
	$IT -A check_if -j RETURN

	# everything else is dropped!
	$IT -A check_if -j LOG_DROP


############################################################

### INPUT ruleset

# loopback is always allowed
$IT -A INPUT -i lo -j ACCEPT

# verify source address <-> interface mapping
$IT -A INPUT -j check_if

# connection tracking:
# - allow all related/established traffic
# - invalid packets MUST be dropped!
$IT -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT
$IT -A INPUT -m state --state INVALID -j LOG_DROP

# SSH
$IT -A INPUT -p tcp --dport 22 -j ACCEPT

# publicly accessible services
#XXX:
#for PORT in 25 465 80 443; do
#	$IT -A INPUT -p tcp --dport $PORT -j ACCEPT
#done

# cerise
$IT -A INPUT -p tcp --dport 8811 -j ACCEPT
$IT -A INPUT -p tcp --dport 8843 -j ACCEPT
$IT -A INPUT -p tcp --dport 443 -j ACCEPT

# bacula FD from backup server
$IT -A INPUT -p tcp --dport 9102 -s $BACKUP -j ACCEPT

# zabbix agent
$IT -A INPUT -p tcp --dport 10050 -s $MONITOR -j ACCEPT

# icmp
$IT -A INPUT -p icmp -m limit --limit 10/second -j ACCEPT
$IT -A INPUT -p icmp -j LOG_DROP

# ignore junk from windows servers (samba)
for PORT in 137 138; do 
	$IT -A INPUT -p udp --dport $PORT -j DROP
done

# default rule
$IT -A INPUT -j LOG_REJECT


############################################################
### FORWARD ruleset

# verify source address <-> interface mapping
$IT -A FORWARD -j check_if

# connection tracking:
# - allow all related/established traffic
# - invalid packets MUST be dropped!
$IT -A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT
$IT -A FORWARD -m state --state INVALID -j LOG_DROP

## intranet zatial moze vsetko vsade
##XXX: ak pribudnu siete, toto treba prerobit
#$IT -A FORWARD -s $NET_VPN0 -j ACCEPT

# niektore stroje mozu robit 9103 (bacula) na backup
#for HOST in $NET_VPN0 92.240.244.101 92.240.244.104; do
#  $IT -A FORWARD -s $HOST -d $BACKUP -p tcp --dport 9103 -j ACCEPT
#done

# default rule
$IT -A FORWARD -j LOG_DROP

############################################################
### NAT ruleset

# SNAT von
#$IT -t nat -A POSTROUTING -s $NET_VPN0 -o wan0 -j SNAT --to 92.240.244.52

# SNAT dnu
#$IT -t nat -A PREROUTING -i wan0 -p tcp --dport 9103 -j DNAT --to-destination $BACKUP
