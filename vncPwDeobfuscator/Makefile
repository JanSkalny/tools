CC=gcc
SVN_REV=$(shell svnversion -n .)
CFLAGS= -g -Wall -O2 
LIBS= 
DEPEND := makedepend
SRCS= d3des.c vncpw.c
OBJS= d3des.o vncpw.o
DEPS=

OSNAME=$(shell uname -s)
CPUNAME=$(shell uname -m)

ifeq ($(OSNAME),Linux)
	LIBS := $(LIBS) -pthread
endif

all: vncpw dep undep

dep: $(SRCS)
	-$(DEPEND) $(CFLAGS) $(SRCS) 2> /dev/null

undep:
	-$(DEPEND) $(CFLAGS) 2> /dev/null

# binarky
vncpw: $(OBJS)
	$(CC) $(CFLAGS) $(LIBS) -o vncpw $(OBJS)

# code
%.o: %.c 
	$(CC) $(CFLAGS) -c -o $@ $<

# cleanup
clean:
	$(DEPEND) $(COPTS)
	-rm -f vncpw $(OBJS)

# DO NOT DELETE
