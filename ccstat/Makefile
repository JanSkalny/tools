CC=gcc
SVN_REV= $(shell svnversion -n .)
CFLAGS= -Wall -O2 -D'SVN_REV="$(SVN_REV)"'
LIBS= -lrt -lpcap -lGeoIP
DEPEND := makedepend
SRCS= ccstat.c stdafx.c
OBJS= ccstat.o stdafx.o
DEPS=
OUT=ccstat

OSNAME=$(shell uname -s)
CPUNAME=$(shell uname -m)

#
all: dep $(OUT) undep

dep: $(SRCS)
	-$(DEPEND) $(CFLAGS) $(SRCS) 2> /dev/null

undep:
	-$(DEPEND)

install: all
	mkdir /var/lib/ccstat/
	chown nobody:nogroup /var/lib/ccstat/
	cp cron.conf /etc/cron.d/ccstat
	cp ccstat /usr/local/bin/
	cp ccstat_* /usr/local/bin/
	cp ccstat /usr/local/bin/
	cp ccstat /usr/local/bin/
	cat ccstat_email_totals | sed "s/HOSTNAME/$(shell uname -n)/" > /usr/local/bin/ccstat_email_totals
	service cron restart

# binaries
$(OUT): $(OBJS)
	$(CC) $(CFLAGS) -o $(OUT) $(OBJS) $(LIBS)

%.o: %.c
	$(CC) $(CFLAGS) -c -o $@ $<

clean:
	$(DEPEND)
	-rm -f $(OUT) $(OBJS)

# DO NOT DELETE this line ; make dep uses this
