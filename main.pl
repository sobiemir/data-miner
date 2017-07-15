#!/usr/bin/perl

use strict;
use warnings;

open( my $file, '<', './test/moss/tst/array_test.c' ) or
	die( 'File canno be opened!' );

my @scopes = ();

			# scopes = []
			# index  = 0
			# ident  = ''

			# while index < data.length do
			# 	char = data[index]

			# 	if( char == "\t" || char == ' ' ) then
			# 		ident += char
			# 		index += 1
			# 		next
			# 	elsif( char == "\n" || char == "\r" ) then
			# 		ident  = ''
			# 		index += 1
			# 		next
			# 	elsif( char == '#' ) then
			# 		scopes << ''
			# 		# index = get_macro_line( data, index, scopes[-1] )
			# 	elsif( char == '/' ) then
			# 		scopes << ''
			# 		# index = get_comment( data, index, scopes[-1] )
			# 	else
			# 		scopes << ident
			# 		# index = get_symbol( data, index, scopes[-1] )
			# 	end
			# 	index += 1
			# end

sub get_macro_line
{
	$_[1] .= 'a';
}

my $data = '';

while( defined(my $char = getc($file)) )
{
	if( $char eq "\t" || $char eq ' ' || $char eq "\n" || $char eq "\r" )
	{
		next;
	}
	elsif( $char eq '#' )
	{
		get_macro_line( $file, $data );
		print $data . "\n";
	}
	elsif( $char eq '/' )
	{
		# get_comment_line( $file, $data );
	}
	else
	{
		# get_symbol( $file, $data );
	}
}

