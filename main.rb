#!/usr/bin/env ruby

require_relative "./gramma.rb"

require_relative "./c/helpers.rb"

class Application

	attr_reader :gramma

	def initialize
		@gramma = Zaskroniec::Gramma.new
		@gramma.get_rules_from_file( "./fsharp/gramma.zlr" )
	end

end

# app = Application.new()


# data = File.read( "./test/main.c" )
data = File.read( "./test/moss/tst/array_test.c" )
Zaskroniec::Helpers.scope_extract( data )


# KZ_BLOCK_ELEMENTS = {}
# KZ_INLINE_ELEMENTS = {}

# dołącz pliki
# require_relative './elements/Header.rb'
# require_relative './elements/AutoParagraph.rb'
# require_relative './elements/Paragraph.rb'
# require_relative './elements/CodeBlock.rb'
# require_relative './elements/Output.rb'
# require_relative './elements/OrderedList.rb'
# require_relative './elements/UnorderedList.rb'

# pobierz folder w którym będą poszukiwane pliki .kex
# $KexFolder = ''
# ARGV.each do |arg|
# 	$KexFolder = File.join( arg, '' )
# end

=begin
//
// Wyświetla błąd i zamyka program.
//
// Parametry:
// => index    Indeks wystąpienia błędu.
// => file     Plik w którym wystąpił błąd.
// => message  Wiadomość do wyświetlenia.
//
=end
# def display_error( index, file, message )

# 	print ":: Error in file #{file} on line #{index}\n"
# 	print ":: #{message}\n"
# 	exit

# end

# def html_special_chars( line )
# 	return line.gsub( /[\&\<\>]/, '&' => '&amp;', '<' => '&lt', '>' => '&gt' );
# end

# def parse_kex_file( file )

# 	$output      = ''
# 	$blockparser = []
# 	$autoparser  = true
# 	$nestedindex = 0
# 	$activeindex = 0

# 	# przechodź po każdej linii w pliku
# 	File.foreach(file).with_index do |line, index|

# 		# wyszukaj indeks od którego rozpoczyna się linia, pomijając białe znaki
# 		$index = line.index( /\S/ )

# 		# parser wewnętrzny funkcji wraz z wywołaniem parsera aktualnego elementu blokowego
# 		if !$autoparser && $blockparser[$nestedindex] != nil && ($index == nil || ($index != nil && $index > $nestedindex))
# 			$output += KZElement.method( $blockparser[$nestedindex] ).call( nil, line, $nestedindex,
# 				if $index == nil then 0 else $index end )
# 			next
# 		end

# 		# tekst nie może być umieszczony w elemencie głównym
# 		if $index != nil && line[$index] == '#'
# 			# rozdziel poszczególne składowe elementu
# 			$data    = line.strip.split
# 			$element = line[($index+1)..(line.index(':') - 1)]

# 			# sprawdź czy parser dla danego elementu istnieje
# 			if KZ_BLOCK_ELEMENTS[$element] == nil
# 				display_error( index, file, "Parser for element #{$element} doesn't exist!" )
# 			end

# 			# koniec konkretnego bloku
# 			if( $index <= $nestedindex && $blockparser[$nestedindex] != nil )
# 				while $nestedindex >= $index do
# 					$output += KZElement.method( $blockparser[$nestedindex] ).call( true, true, $nestedindex, $index )
# 					$nestedindex = $nestedindex-1
# 				end
# 			end

# 			# zapisz parser elementu blokowego i uruchom go po raz pierwszy
# 			$blockparser[$index] = KZ_BLOCK_ELEMENTS[$element]
# 			$output += KZElement.method( $blockparser[$index] ).call( $data, nil, $nestedindex, $index )

# 			# sprawdź czy parser będzie sam przetwarzał wewnętrzne elementy
# 			$autoparser  = !KZElement.method( "#{$blockparser[$index]}_HasCustomParser" ).call( $data )
# 			$nestedindex = $index

# 		# błąd - tekst musi być umieszczony w jakimś elemencie
# 		elsif $index == 0
# 			display_error( index, file, 'Text must be placed in inner element!' )

# 		# parser automatyczny wraz z wywołaniem parsera aktualnego elementu blokowego
# 		elsif $blockparser[$nestedindex] != nil

# 			# koniec konkretnego bloku
# 			if( $index != nil && $index <= $nestedindex && $blockparser[$nestedindex] != nil )
# 				while $nestedindex >= $index do
# 					$output += KZElement.method( $blockparser[$nestedindex] ).call( true, true, $nestedindex, $index )
# 					$nestedindex = $nestedindex-1
# 				end
# 			end

# 			$output += KZElement.method( $blockparser[$nestedindex] ).call( nil, line, $nestedindex,
# 				if $index == nil then 0 else $index end )
# 			next
# 		end
# 	end

# 	# zakończ pozostałe tagi
# 	while $nestedindex >= 0 do
# 		$output += KZElement.method( $blockparser[$nestedindex] ).call( true, true, $nestedindex, 0 )
# 		$nestedindex = $nestedindex-1
# 	end

# 	File.open( 'test.html', 'w' ) { |file| file.write($output) }

# end

# def get_kex_files_and_parse( folder )

# 	Dir[folder + '*.kex'].each do |file|
# 		parse_kex_file( file )
# 	end

# end

# # wyszukuje wszystkie pliki o rozszerzeniu .kex i konwertuje je na html
# get_kex_files_and_parse( $KexFolder )
