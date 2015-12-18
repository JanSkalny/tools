#include <stdlib.h>
#include <stdio.h>
#include <string.h>

#include "d3des.h"

unsigned char key[] = { 23,82,107,6,35,78,88,7 };

unsigned char c2x(unsigned char c)
{
	if (c <= '9' && c >= '0')
		return c-'0';
	else if (c <= 'f' && c >= 'a')
		return c-'a'+10;	
	return -1;	// epic fail
}

// unpack("H*", input)
int unpack_hex(char *input, int input_len, unsigned char *output) 
{
	int i, len;

	if (input_len%2)
		return -1;

	len = input_len/2;

	for (i=0; i<len; i++) {
		output[i] = c2x(input[i*2]) << 4;
		output[i] |= c2x(input[i*2+1]);
	}

	return 0;
}

int main(int argc, char**argv) 
{
	unsigned char cleartext[9], pw[8];

	if (argc != 2)
		exit(1);

	if (strlen(argv[1]) != 16)
		exit(2);

	unpack_hex(argv[1], 16, pw);
	deskey(key, DE1);
	des(pw, cleartext);
	cleartext[8] = 0;
	printf("%s\n", cleartext);

	return 0;
}


