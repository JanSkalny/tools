import pprint
import struct

def imsi_to_bytes(imsi_string):
	imsi_digits = list(imsi_string)
	imsi_bytes = [0,0,0,0,0,0,0,0]
	digit_no = 0

	# imsi is too long... fetch only first 15 characters and nag..
	if len(imsi_digits) > 15:
		print "imsi too long... "
		imsi_digits = imsi_digits[:15]

	# lower 3 bits indicate IMSI number... (0x1)
	imsi_bytes[0] = 0x1

	# convert string to bytes... 
	for digit in imsi_digits:
		byte_no = (digit_no+1) / 2;
		if digit_no % 2: 
			imsi_bytes[byte_no] |= int(digit, 16)
		else:
			imsi_bytes[byte_no] |= int(digit, 16) << 4
		digit_no += 1
	
	# if we have odd number of digits, set 4th bit to 1
	# and fill last octets upper nibble with 0xf
	if not len(imsi_digits)%2:
		imsi_bytes[0] |= 0x8
		imsi_bytes[(digit_no+1)/2] |= 0xf0
	
	return imsi_bytes

def imsi_to_bcd(imsi_string):
	imsi_bytes = imsi_to_bytes(imsi_string)
	return struct.pack("BBBBBBBB", *imsi_bytes)

def test(imsi):
	print "str="+imsi+" bcd=0x"+(imsi_to_bcd(imsi).encode("hex"))

imsis = [ "231014450469959", "2310144504699591", "23101445046995" ]
for imsi in imsis:
	test(imsi)
