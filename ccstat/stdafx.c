#include "stdafx.h"

void xlog(const char *format, ...) {
	va_list args;
	char buf[1024];

	va_start(args, format);
	vsnprintf(buf, sizeof(buf), format, args);
	va_end(args);

	fprintf(stdout, "%s\n", buf);
}

void die(const char *format, ...) {
	va_list args;
	char buf[1024];

	va_start(args, format);
	vsnprintf(buf, sizeof(buf), format, args);
	va_end(args);

	fprintf(stderr, "%s\n", buf);

	exit(1);
}

