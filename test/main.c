#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#define SAMPLE_IFDEF

#define SAMPLE_MACRO_VARIABLE 0x12

#define SAMPLE_MACRO_FUNCTION(a, b, c) (a * b + c)

#define SAMPLE_MACRO_MULTILINE_VARIABLE \
	return (\
		x == 0 && \
		w == 5 \
	)

#define SAMPLE_MACRO_MULTILINE_FUNCTION(a, b, \
	c) \
	return ( \
		a * b \
		+ c)

inline int prepare_data( const char *dat, unsigned int x )
{
	int ret = 0;
	while( x )
		ret |= ret * dat[x];
	return ret;
}

extern
inline int prepare_data( const char *dat, unsigned int x );

int main( int argc, char **argv )
{
	const char data[] = "standard element";

	printf( "%d\n", prepare_data(data, strlen(data) - 1) );
	return 0;
}
