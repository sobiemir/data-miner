#!/usr/bin/ruby

module Zaskroniec

	Struct.new(
		"Symbol",
		:Type,
		:Name,
		:Symbols,
		:Start,
		:Parent
	)

	ZC_PARSE_STARTED   = 0x001
	ZC_PARSE_NAMESPACE = 0x002
	ZC_PARSE_MODULE    = 0x004
	ZC_PARSE_LET       = 0x008
	ZC_PARSE_TYPE      = 0x010
	ZC_PARSE_OPEN      = 0x020

	class Parser

		attr_reader :entities

		def extract_scope( data )

			
			
		end

		def new_symbol( type = :None, parent = nil, start = -1, name = '' )
			return Struct::Symbol.new( type, name, [], start, parent )
		end

		def initialize
			@entities = []
		end

		def skip_whitespaces( data, index )
			while data[index] == ' ' || data[index] == '\n' || data[index] == '\r' ||
				  data[index] == '\t' do
				index += 1
			end
			return index
		end

		def get_identifiers( data )

			# Całe wyrażenie zbudowane z pomocą z dokumentacji F#
			# http://fsharp.org/specs/language-spec/4.0/FSharpSpec-4.0-latest.pdf
			# Sekcja identyfikatorów i słów kluczowych

			regex_identifier = %r{
				([ ]*)(module|namespace|open|let|type|exception|member)   # identyfikator
				[\s]* 
				(public|private|internal)?
				[\s]*
				(rec|mutable|inline)?
				[\s]*
				(
					(?:[\p{L}\p{Nl}][0-9\p{L}\p{Nl}\p{Pc}\p{Mn}\p{Mc}\p{Cf}'_.]*)
					|
					(?:``(?:[^`\n\r\t]|`[^`\n\r\t])+``)
				)
				([^;=\n\r]*)
				(?:=[\s]*(function|struct))*
			}x

			return data.scan( regex_identifier )

		end

		def parse_file( file )

			data  = File.read( file )

			get_identifiers( data ).each { |elem| print elem.to_s + "\n" }

			# level_inside = 0x000
			# level_deep   = 0x000

			# can_be     = 0x00E
			# is_parsing = 0x000
			# skip_index = 0x000
			# entity     = nil
			# symbols    = @entities


			# File.foreach( file ).with_index do |line, line_index|

			# 	line.each_char.with_index do |char, char_index|

			# 		# Omijanie poszczególnych elementów.
			# 		if skip_index > 0 then
			# 			skip_index -= 1
			# 			next
			# 		end

			# 		# Wszystkie wcięcia tworzone w F# powinny być tworzone znakami spacji.
			# 		if( char == ' ' || char == "\n" || char == "\r" ) then
			# 			if( is_parsing > 0 && (is_parsing & ZC_PARSE_STARTED) == 0 ) then
			# 				next
			# 			end
			# 			is_parsing = 0
			# 		end
			# 		if( is_parsing > 0 && entity != nil ) then
			# 			is_parsing |= ZC_PARSE_STARTED
			# 			entity.Name << char
			# 			next
			# 		end

			# 		# Przechwytywanie nazwy przestrzeni.
			# 		# Można tworzyć przestrzenie nazw wewnątrz innych przestrzeni, jednak nie w normalny sposób.
			# 		# 
			# 		# namespace Outer
			# 		# ...
			# 		# namespace Outer.Inner
			# 		# 
			# 		# Nie da się utworzyć przestrzeń wewnątrz innej bez podania nazwy przestrzeni zewnętrznej.
			# 		if( char == 'n' && (can_be & ZC_PARSE_NAMESPACE) > 0 ) then
			# 			entity_index = line.index( 'namespace ', char_index )

			# 			# level_inside w namespace zawsze jest 1
			# 			if( entity_index == char_index ) then
			# 				level_inside = 1
			# 				@entities.push( new_symbol(:Namespace, @entities, level_deep) )

			# 				entity  = @entities[-1]
			# 				symbols = @entities[-1].Symbols

			# 				is_parsing = ZC_PARSE_NAMESPACE
			# 				skip_index = 9
			# 				next
			# 			end

			# 		# Przechwytywanie nazwy modułu.
			# 		# Można tworzyć moduły wewnątrz innych modułów.
			# 		# W przypadku gdy moduł jest tworzony jako pierwszy, można go tworzyć jak przestrzeń nazw.
			# 		# 
			# 		# module Outer
			# 		# ...
			# 		#     module Inner =
			# 		#     ...
			# 		# 
			# 		# Jak widać moduły wewnętrzne potrzebują znaku =, dla pierwszego jest on zbędny.
			# 		# W przypadku gdy zdefiniowana została przestrzeń, pierwszy moduł również potrzebuje znaku =.
			# 		elsif( char == 'm' && (can_be & ZC_PARSE_MODULE) > 0 ) then
			# 			entity_index = line.index( 'module ', char_index )

			# 			if( entity_index == char_index ) then
			# 				level_inside += 1
			# 				symbols.push( new_symbol(:Module, symbols, level_deep) )

			# 				entity  = symbols[-1]
			# 				symbols = symbols[-1].Symbols

			# 				is_parsing = ZC_PARSE_MODULE
			# 				skip_index = 6
			# 			end
				
			# 		# elsif( char == 'l' && (can_be & ZC_PARSE_LET) ) then
			# 		# 	entity_index = line.index( 'let ', char_index )

			# 		# 	if( entity_index == char_index ) then
			# 		# 		level_inside += 1
			# 		# 		symbols.push( new_symbol(:Let, symbols, level_deep) )

			# 		# 		is_parsing = ZC_PARSE_MODULE
			# 		# 		skip_index = 3
			# 		# 	end
			# 		end



			# 		# line =~ /[\s]*open [a-zA-Z\.\_]+/ != nil 

			# 		# if( char == 'o' ) then
			# 		# 	open_index = line.index( 'open', char_index )

			# 		# 	if( open_index != nil ) then
			# 		# 		@imports.push( line.gsub('open', '').strip )
			# 		# 		break
			# 		# 	end
			# 		# elsif( char == 'm' ) then
			# 		# 	module_index = line.index( 'module', char_index )

			# 		# 	if( module_index != nil ) then
			# 		# 		@modules.push( line.gsub('module', '').strip )
			# 		# 		break
			# 		# 	end
			# 		# end

			# 	end

			# end

		end

	end

	parser = Parser.new
	parser.parse_file( "../test/main.fs" )

	print parser.entities

end
