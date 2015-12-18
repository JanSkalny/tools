#include "stdafx.h"

#define OUTPUT_PATH "/var/lib/ccstat"
#define DEV "eth0"
#define SNAP_LEN 38

typedef struct record {
	uint64_t src;
	uint64_t dst;
	uint32_t addr;
	char *country;
	struct record *next;
} t_record;

typedef struct {
	t_record* map[256];
} t_record_list;

t_record_list *records;
GeoIP *geoip;
struct tm *start;

uint8_t hash_addr(uint32_t addr) {
	return (addr>>24) & 0xFF;
}

t_record_list* create_record_list() {
	t_record_list *p;

	p = malloc(sizeof(t_record_list));
	if (!p)
		die("malloc failed");
	memset(p, 0, sizeof(t_record_list));

	return p;
}

void destory_record_list(t_record_list *records) {
	int i;
	t_record *p, *q;

	for (i=0; i!=256; i++) {
		p = records->map[i];
		while(p) {
			q = p;
			p = p->next;
			if (q->country)
				free(q->country);
			free(q);
		}
	}

	free(records);
}

void dump_records(t_record_list *records, FILE *f) {
	struct in_addr addr;
	t_record *p;
	int i;

	for (i=0; i!=256; i++) {
		p = records->map[i];
		while (p) {
			addr.s_addr = htonl(p->addr);
			fprintf(f, "%s %ld %ld %s\n", inet_ntoa(addr), p->src, p->dst, p->country);
			p = p->next;
		}
	}
}

t_record* create_record(t_record_list *record_list, uint32_t addr) {
	const char *country;
	t_record *p;
	int i;

	// create blank record
	p = malloc(sizeof(t_record));
	if (!p) 
		die("malloc failed");
	memset(p, 0, sizeof(t_record));

	// and insert it into our hash map
	i = hash_addr(addr);
	p->addr = addr;
	p->next = records->map[i];
	records->map[i] = p;

	// lookup geoip region
	country = GeoIP_country_code_by_ipnum(geoip, addr);
	if (country)
		p->country = strdup(country);

	//xlog("new host %08xh (%s)", addr, p->country);

	return p;
}

t_record* find_record(t_record_list *records, uint32_t addr) {
	uint8_t i;
	t_record *p;

	i = hash_addr(addr);
	p = records->map[i];

	while (p) {
		if (p->addr == addr)
			break;
		p = p->next;
	}

	if (!p) 
		p = create_record(records, addr);
	
	return p;
}

void process_packet(t_record_list *records, uint32_t src_ip, uint32_t dst_ip, int len) {
	t_record *p;

	p = find_record(records, src_ip);
	p->src += len;

	p = find_record(records, dst_ip);
	p->dst += len;
}

/**
 * CTRL^C and kill signal handler
 *  - write statistics into output file
 */
static void signal_handler(int signo) {
	char path[200], buf[100];
	FILE *f;

	strftime(buf, sizeof(buf), "%Y-%m-%d_%H%I%S", start);
	sprintf(path, "%s/%s.stats", OUTPUT_PATH, buf);
	f = fopen(path, "w");
	xlog("saving stats to %s...", path);
	dump_records(records, f);
	fclose(f);

	exit(0);
}


int main()
{
	char err[PCAP_ERRBUF_SIZE];
	pcap_t *pcap;
	time_t now;
	struct pcap_pkthdr hdr;
	const uint8_t *data;
	struct ether_header *eth;
	uint32_t src_ip, dst_ip;

	// signal handler
	signal(SIGINT, signal_handler);
	signal(SIGUSR1, signal_handler);

	// load geoip database
	geoip = GeoIP_open("/usr/share/GeoIP/GeoIP.dat", GEOIP_MEMORY_CACHE | GEOIP_CHECK_CACHE);
	if (!geoip)
		die("failed to open geoip database");

	// create record list
	records = create_record_list();

	// mark current time, for dump purposes
	time(&now);
	start = localtime(&now);

	// open pcap capture device
	pcap = pcap_open_live(DEV, SNAP_LEN, 0, 1000, err);
	if (!pcap)
		die("pcap_open_live: %s", err);

	while(1) {
		// receive next packet
    data = pcap_next(pcap, &hdr);

		if (!data)
			continue;
		if (hdr.caplen < 32) 
			continue;

		// parse L2 data
		eth = (struct ether_header*)data;
		data += 14;
		if (ntohs(eth->ether_type) != ETHERTYPE_IP) 
			continue;
		
		// parse IPv4
		if ((data[0]&0xf0) != 0x40)
			continue;

		src_ip = ntohl(*((uint32_t*)(data+12)));
		dst_ip = ntohl(*((uint32_t*)(data+16)));
	
		process_packet(records, src_ip, dst_ip, hdr.len);
	}

	return 0;
}

