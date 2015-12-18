#pragma once

#include <unistd.h>
#include <stdint.h>
#include <stdio.h>
#include <stdarg.h>
#include <stdlib.h>
#include <string.h>
#include <signal.h>
#include <time.h>
#include <arpa/inet.h>
#include <netinet/if_ether.h>
#include <arpa/inet.h>

#include <pcap.h>
#include <GeoIP.h>

void xlog(const char *format, ...);
void die(const char *format, ...);

