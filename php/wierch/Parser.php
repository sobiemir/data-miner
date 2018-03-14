<?php
namespace Wierch;

class Parser
{
	private $_directory = '';
	private $_pos = 0;
	private $_length = 0;

	public function setDirectory( string $dir ): void
	{
		$this->_directory = $dir;
	}

	public function parse( string $start_point = 'index' ): void
	{
		$files = $this->_getFiles();

		if( isset($files[$start_point]) )
			$this->_parseFile( $files[$start_point]['path'] );
		else
			throw new Exception( 'Starting point not exist!' );
	}

	private function _parseFile( string $path ): void
	{
		$handle = fopen( $path, 'r' );
		if( !$handle )
			throw new Exception( 'File doesn\'t exist!' );

		$attributes = [];

		$line = '';
		while( ($line = fgets($handle)) !== false )
		{
			$length = strlen( $line );
			$index = $this->_skipWhitespace( $line, $length );

			if( $index == -1 )
				continue;

			if( $index + 3 > $length )
				continue;

			$c1 = $line[$index + 0];
			$c2 = $line[$index + 1];
			$c3 = $line[$index + 2];

			if( $c1 == '>' && $c2 == '>' )
			{
				// znacznik
			}
			// atrybut
			else if( $c1 == '>' && $c2 == '$' && $c3 == '$' )
			{
				$index += 3;
				$key = $this->_grabText( $line, $index, $length );

				if( $data == '' )
					continue;

				$index += strlen($key);
				$value = $this->_grabText( $line, $index, $length );

				$attributes[$key] = $value == ''
					? false
					: $value;
			}
		}
		fclose( $handle );
	}

	private function _grabText( string $line, int $index, int $length ): string
	{
		while( $index < $length )
			if( $line[$index] != ' ' && $line[$index] != "\t" )
				break;
			else
				++$index;

		$data = '';
		while( $index < $length )
			if( $line[$index] != ' ' && $line[$index] != "\t" )
				$data += $line[$index++];
			else
				break;

		return $data;
	}

	private function _skipWhitespace( string &$line, $length ): int
	{
		$index = 0;
		while( $index < $length )
			switch( $line[$index] )
			{
				case ' ':
				case "\t":
				case "\n":
				case "\r":
					++$index;
				break;
				default:
					return $index;
			}

		return -1;
	}

	private function _getFiles( string $path = null ): array
	{
		if( $path == null )
			$path = $this->_directory;

		$directories = [];
		$iterator = new \DirectoryIterator( $path );

		$retval = [];

		foreach( $iterator as $entity )
		{
			if( $entity->isDot() || $entity->isLink() )
				continue;

			if( $entity->isDir() )
			{
				$entry = [
					'type'     => 'DIR',
					'files'    => 0,
					'title'    => '',
					'modify'   => $entity->getMTime(),
					'path'     => $entity->getRealPath(),
					'children' => []
				];
				$children = $this->getFiles( $entity->getRealPath() );

				$entry['files'] = count( $children );
				$entry['children'] = $children;

				$retval[$entity->getFilename()] = $entry;
			}
			else
				$retval[$entity->getFilename()] = [
					'type'     => 'FILE',
					'files'    => 0,
					'title'    => '',
					'modify'   => $entity->getMTime(),
					'path'     => $entity->getRealPath(),
					'children' => []
				];
		}
		return $retval;
	}
}

// #!/usr/bin/env ruby

// KZ_BLOCK_ELEMENTS = {}
// KZ_INLINE_ELEMENTS = {}

// # dołącz pliki
// require_relative './elements/Header.rb'
// require_relative './elements/AutoParagraph.rb'
// require_relative './elements/Paragraph.rb'
// require_relative './elements/CodeBlock.rb'
// require_relative './elements/Output.rb'
// require_relative './elements/OrderedList.rb'
// require_relative './elements/UnorderedList.rb'

// # pobierz folder w którym będą poszukiwane pliki .kex
// $KexFolder = ''
// ARGV.each do |arg|
// 	$KexFolder = File.join( arg, '' )
// end

// =begin
// //
// // Wyświetla błąd i zamyka program.
// //
// // Parametry:
// // => index    Indeks wystąpienia błędu.
// // => file     Plik w którym wystąpił błąd.
// // => message  Wiadomość do wyświetlenia.
// //
// =end
// def display_error( index, file, message )

// 	print ":: Error in file #{file} on line #{index}\n"
// 	print ":: #{message}\n"
// 	exit

// end

// def html_special_chars( line )
// 	return line.gsub( /[\&\<\>]/, '&' => '&amp;', '<' => '&lt', '>' => '&gt' );
// end

// def parse_kex_file( file )

// 	$output      = ''
// 	$blockparser = []
// 	$autoparser  = true
// 	$nestedindex = 0
// 	$activeindex = 0

// 	# przechodź po każdej linii w pliku
// 	File.foreach(file).with_index do |line, index|

// 		# wyszukaj indeks od którego rozpoczyna się linia, pomijając białe znaki
// 		$index = line.index( /\S/ )

// 		# parser wewnętrzny funkcji wraz z wywołaniem parsera aktualnego elementu blokowego
// 		if !$autoparser && $blockparser[$nestedindex] != nil && ($index == nil || ($index != nil && $index > $nestedindex))
// 			$output += KZElement.method( $blockparser[$nestedindex] ).call( nil, line, $nestedindex,
// 				if $index == nil then 0 else $index end )
// 			next
// 		end

// 		# tekst nie może być umieszczony w elemencie głównym
// 		if $index != nil && line[$index] == '#'
// 			# rozdziel poszczególne składowe elementu
// 			$data    = line.strip.split
// 			$element = line[($index+1)..(line.index(':') - 1)]

// 			# sprawdź czy parser dla danego elementu istnieje
// 			if KZ_BLOCK_ELEMENTS[$element] == nil
// 				display_error( index, file, "Parser for element #{$element} doesn't exist!" )
// 			end

// 			# koniec konkretnego bloku
// 			if( $index <= $nestedindex && $blockparser[$nestedindex] != nil )
// 				while $nestedindex >= $index do
// 					$output += KZElement.method( $blockparser[$nestedindex] ).call( true, true, $nestedindex, $index )
// 					$nestedindex = $nestedindex-1
// 				end
// 			end

// 			# zapisz parser elementu blokowego i uruchom go po raz pierwszy
// 			$blockparser[$index] = KZ_BLOCK_ELEMENTS[$element]
// 			$output += KZElement.method( $blockparser[$index] ).call( $data, nil, $nestedindex, $index )

// 			# sprawdź czy parser będzie sam przetwarzał wewnętrzne elementy
// 			$autoparser  = !KZElement.method( "#{$blockparser[$index]}_HasCustomParser" ).call( $data )
// 			$nestedindex = $index

// 		# błąd - tekst musi być umieszczony w jakimś elemencie
// 		elsif $index == 0
// 			display_error( index, file, 'Text must be placed in inner element!' )

// 		# parser automatyczny wraz z wywołaniem parsera aktualnego elementu blokowego
// 		elsif $blockparser[$nestedindex] != nil

// 			# koniec konkretnego bloku
// 			if( $index != nil && $index <= $nestedindex && $blockparser[$nestedindex] != nil )
// 				while $nestedindex >= $index do
// 					$output += KZElement.method( $blockparser[$nestedindex] ).call( true, true, $nestedindex, $index )
// 					$nestedindex = $nestedindex-1
// 				end
// 			end

// 			$output += KZElement.method( $blockparser[$nestedindex] ).call( nil, line, $nestedindex,
// 				if $index == nil then 0 else $index end )
// 			next
// 		end
// 	end

// 	# zakończ pozostałe tagi
// 	while $nestedindex >= 0 do
// 		$output += KZElement.method( $blockparser[$nestedindex] ).call( true, true, $nestedindex, 0 )
// 		$nestedindex = $nestedindex-1
// 	end

// 	File.open( 'test.html', 'w' ) { |file| file.write($output) }

// end

// def get_kex_files_and_parse( folder )

// 	Dir[folder + '*.kex'].each do |file|
// 		parse_kex_file( file )
// 	end

// end

// # wyszukuje wszystkie pliki o rozszerzeniu .kex i konwertuje je na html
// get_kex_files_and_parse( $KexFolder )
