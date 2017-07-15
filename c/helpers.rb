module Zaskroniec

	class Helpers

		def self.get_macro_line( data, length, index, scope )
			itsnotend = false

			while( index < length ) do
				if( data[index] == "\r" || data[index] == "\n" ) then
					if( data[index] == "\r" && data[index + 1] == "\n" ) then
						index += 1
					end
					if( itsnotend ) then
						itsnotend = false
						scope    << "\n"
						index    += 1
						next
					end
					break
				elsif( data[index] == '\\' ) then
					itsnotend = true
					index    += 1
					next
				elsif( itsnotend )
					scope    << '\\'
					itsnotend = false
				end

				scope << data[index]
				index += 1
			end
			return index
		end

		def self.get_comment( data, length, index, scope )
			if( data[index + 1] == '*' ) then
				while( index < length ) do
					if( data[index] == '*' && data[index + 1] == '/' ) then
						scope << '*/'
						index += 1
						break
					end

					scope << data[index]
					index += 1
				end
			elsif( data[index + 1] == '/' ) then
				index = get_macro_line( data, index, scope )
			end
			return index
		end

		def self.get_symbol( data, length, index, scope )
			level = 0

			while( index < length ) do
				if( data[index] == '{' ) then
					level += 1
				elsif( data[index] == '}' ) then
					level -= 1
					if( level == 0 ) then
						scope << data[index]
						index += 1
						break
					end
				elsif( level == 0 && data[index] == ';' ) then
					scope << ';'
					index += 1
					break
				end
				scope << data[index]
				index += 1
			end
			return index
		end

		def self.indexOfNonWhite( data, index = 0 )
			while( index < length ) do
				if( data[index] != ' '  && data[index] != "\t" &&
					data[index] != "\r" && data[index] != "\n" ) then
					return index
				end
				index += 1
			end
			return nil
		end

		def self.scope_extract( data )
			scopes = []
			index  = 0
			length = data.length
			line   = ''

			data.each_char do |chr|
				if( chr == "\t" || chr == ' ' || chr == "\n" || chr == "\r" ) then
					# index += 1
					next
				elsif( chr == '#' ) then
					# index = get_macro_line( data, length, index, line )
					line  = ''
				elsif( chr == '/' ) then
					# index = get_comment( data, length, index, line )
					line  = ''
				else
					# index = get_symbol( data, length, index, line )
					line  = ''
				end
			end

			# while index < length do
			# 	char = data[index]

			# 	# if( char == "\t" || char == ' ' || char == "\n" || char == "\r" ) then
			# 	# 	index += 1
			# 	# 	next
			# 	# elsif( char == '#' ) then
			# 	# 	index = get_macro_line( data, length, index, line )
			# 	# 	line  = ''
			# 	# elsif( char == '/' ) then
			# 	# 	index = get_comment( data, length, index, line )
			# 	# 	line  = ''
			# 	# else
			# 	# 	index = get_symbol( data, length, index, line )
			# 	# 	line  = ''
			# 	# end
			# 	index += 1
			# end

			# scopes.each do |scope|
			# 	index = self.indexOfNonWhite( scope )
			# 	if( index == nil ) then
			# 		next
			# 	elsif( scope[index] == '#' ) then
			# 		index = self.indexOfNonWhite( scope, index + 1 )
			# 		if( index == nil ) then
			# 			next
			# 		elsif( scope[index] == 'i' && scope[index + 1] == 'n' ) then
			# 			print "INCLUDE:\n"
			# 			print scope.scan( /^[\s]*\#[\s]*include[\s]+(?:(?:<([^>]+)>)|(?:"([^"]+)"))/ )
			# 			print "\n"
			# 		elsif( scope[index] == 'd' ) then
			# 			print "DEFINE:\n"
			# 			print scope.scan( /^[\s]*\#[\s]*define[\s]+([a-zA-Z_0-9$]*)(?:\(((?:[\s]*(?:[a-zA-Z_0-9$]+|(?:\.\.\.))[\s]*[,]?[\s]*)*)\))?(?:.*)/m )
			# 			print "\n"
			# 		end
			# 	elsif( scope[index] == '/' ) then
			# 	else
			# 		print "SYMBOL:\n"
			# 		print scope.scan( /^[\s]*((?:[a-zA-Z_0-9$]*[\s]*)+)(?:\(((?:[\s]*(?:[a-zA-Z_0-9$*]+|(?:\.\.\.))[\s]*[,]?[\s]*)*)\))?[\s]*(=)?[\s]*([^;{]*)(?:.*)/m )
			# 		print "\n"
			# 	end
			# end

			# File.open( 'test.html', 'w' ) do |file|
			# 	scopes.each do |scope|
			# 		file.write(scope)
			# 		file.write("\n\n---\n\n")
			# 	end
			# end
			# print scopes
		end

	end


# 	require 'logger'

# 	$LOG = Logger.new( "stat.log" )

# 	Struct.new(
# 		"Comment",
# 		:Begin,
# 		:End,
# 		:Start,
# 		:Type,
# 		:Text,
# 	)

# 	COMMENT_SEPARATOR = "\n"

# 	# () - COMMENT
# 	# {} - COMMENT
# 	# [] - COMMENT
# 	# "" - NO COMMENT
# 	# '' - NO COMMENT

# 	# SCOPE_BEGIN_CHAR_1 = '('
# 	# SCOPE_BEGIN_CHAR_2 = '{'
# 	# SCOPE_BEGIN_CHAR_3 = '['
# 	# SCOPE_BEGIN_CHAR_4 = '"'
# 	# SCOPE_BEGIN_CHAR_5 = "'"

# 	ZC_COMMENT_START = 0x0001
# 	ZC_COMMENT_LINE  = 0x0002
# 	ZC_COMMENT_BLOCK = 0x0003
# 	ZC_COMMENT_STOP  = 0x0004
# 	ZC_COMMENT_KILL  = 0x0005

# 	class Gramma

# 		def create_comment( ib = -1, ie = -1, is = -1, st = :None, text = '' )
# 			return Struct::Comment.new( ib, ie, is, st, text )
# 		end

# 		def getSymbols()

			

# 		end

# 		def getSymbolComments( symbols = [] )
# 		end


# 	# =============================================================================================
# 	# Pobiera komentarz z wybranej linii.
# 	# W przypadku gdy komentarz jest podzielony na kilka linii, należy funkcję po prostu wywołać
# 	# na pozostałych liniach, przekazując tą samą strukturę.
# 	# To samo dotyczy komentarzy blokowych.
# 	# 
# 	# Params:
# 	#     line : Linia z której komentarz ma zostać pobrany.
# 	#     start: Indeks startowy w linii, od której komentarz będzie sprawdzany.
# 	#     comm : Struktura do której będą pobierane dane dotyczące komentarza.
# 	# 
# 	# Returns:
# 	#     Indeks zakończenia komentarza, 0 w przypadku gdy komentarz blokowy się nie zakończył
# 	#     lub -1 gdy komentarz nie istnieje na w podanej linii w podanym indeksie.
# 	# =============================================================================================
# 	def self.GetCommentFromLine( line, start, comm )
# 		$index = if start == -1 then line.index( '/', start + 1 ) else start end

# 		if $index == nil then
# 			# brak komentarza w linii
# 			if comm.Type != :Block then
# 				return -1

# 			# lub po prostu ciąg dalszy komentarza blokowego
# 			else
# 				comm.Text << COMMENT_BLOCK_SEPARATOR <<
# 					CommentArtifactRemove( line.strip, 4 )
# 				return 0
# 			end
# 		end

# 		# komentarz liniowy
# 		if line[$index + 1] == '/' then

# 			# komentarz lioniowy ale w bloku
# 			if comm.Type != :None then
# 				comm.End  = line
# 				comm.Type = :SingleBlock
# 				comm.Text << COMMENT_BLOCK_SEPARATOR <<
# 					CommentArtifactRemove( line[($index + 2)..-1].strip, 1 )

# 			# zwykły komentarz liniowy lub początek komentarza liniowego w bloku
# 			else
# 				comm.Begin = line
# 				comm.End   = line
# 				comm.Type  = :Single
# 				comm.Text << COMMENT_BLOCK_SEPARATOR <<
# 					CommentArtifactRemove( line[($index + 2)..-1].strip, 1 )
# 			end
# 			return line.length

# 		# komentarz blokowy
# 		elsif line[$index + 1] == '*' then

# 			# szukaj znacznika kończącego komentarz
# 			$lastl = $index
# 			$index = line.index( '/', $index + 1 )

# 			# początek komentarza blokowego
# 			if $index == nil then
# 				comm.Begin = line
# 				comm.End   = line
# 				comm.Type  = :Block
# 				comm.Text << COMMENT_BLOCK_SEPARATOR <<
# 					CommentArtifactRemove( line[($index + 2)..-1].strip, 3 )
# 				return 0

# 			# komentarz blokowy w linii
# 			else
# 				comm.Begin = line
# 				comm.End   = line
# 				comm.Type  = :BlockLine
# 				comm.Text << COMMENT_BLOCK_SEPARATOR <<
# 					CommentArtifactRemove( line[($lastl + 2)..($index - 2)].strip, 2 )
# 				return $index + 1
# 			end

# 		# zakończenie komentarza blokowego
# 		elsif $index != 0 && line[$index - 1] == '*' then
# 			comm.End = line
# 			comm.Text << COMMENT_BLOCK_SEPARATOR <<
# 				CommentArtifactRemove( line[0..($index - 2)].strip, 5 )
# 			return $index + 1
# 		end

# 		return -1
# 	end

# 	# =============================================================================================
# 	# Pobiera wszystkie komentarze z podanego pliku.
# 	# Parsowany jest język C, więc funkcja oczekuje poprawnych plików o rozszerzeniu .c lub .h.
# 	# 
# 	# Params:
# 	#     file: Nazwa pliku z którego komentarze będą pobierane.
# 	# 
# 	# Returns:
# 	#     Tablicę struktur z informacjami o komentarzach wraz z ich treścią.
# 	# =============================================================================================
# 	def self.GetAllComments( file )

# 		$block    = false    # komentarz blokowy
# 		$single   = false    # komentarz w linii
# 		$lastidx  = 0        # ostatni indeks komentarza liniowego
# 		$comments = []       # lista komentarzy

# 		$index    = -1       # indeks w którym znaleziony został znak rozpoczęcia komentarza
# 		$start    = -1       # indeks ropozczęcia komentarza blokowego
# 		$lstart   = -1
# 		$lindex   = -1

# 		# sprawdź czy podano ścieżkę do pliku
# 		if !file.is_a? String then
# 			print 'You must pass file path to parse it!'
# 			return []
# 		end

# 		# dodaj pusty komentarz na sam koniec
# 		# $comments.push( CreateComment() )

# 		# otwórz plik i szukaj komentarzy
# 		File.foreach( file ).with_index do |line, index|

# 			# $start = line.index( '/', 0 )

# 			# podana linia nie zawiera komentarza
# 			# if $start == nil then
# 				# next
# 			# end

# 				# $index = GetCommentFromLine( line, start, $comments[$comments.length - 1] )

# 				# if $index == -1 then

# 				# end

# 			# komentarz liniowy
# 			# if $index == line.length then
# 				# if $start == $lstart then
# 				# end

# 				# $lstart = $start
# 				# $comments.push( CreateComment() )
# 			# end


# 			# resetuj punkt startowy dla komentarzy blokowych
# 			$start = -1
# 			$index = line.index( '/', 0 )

# 			while $index != nil do

# 				# szukaj zakończenia komentarza blokowego
# 				if $block && $index > 0 then
# 					if $block && line[$index - 1] == '*' then
# 						# zapobiegaj sytuacji /*/, gdzie byłby wykrywany koniec komentarza
# 						if $start == -1 || ($start + 2 != $index) then
# 							$block = false
# 						end

# 						# dodaj treść komentarza do listy - komentarz blokowy, ale w linii
# 						if !$block && $start != -1 then
# 							$comments.push( Struct::Comment.new(
# 								index,
# 								index,
# 								-1,
# 								4,
# 								CommentArtifactRemove(line[($start + 2)..($index - 2)].strip, 2)
# 							) )
# 						# koniec komentarza blokowego wieloliniowego
# 						elsif !$block && $start == -1 then
# 							$comments[$comments.length - 1].Text << "\n" << CommentArtifactRemove(line[0..($index - 2)].strip, 5 )
# 							$comments[$comments.length - 1].End = index
# 						end
# 					end
# 				# szukaj jakiegokolwiek komentarza
# 				elsif !$block then
# 					if line[$index + 1] == '/' then
# 						$single = true
# 					elsif line[$index + 1] == '*' then
# 						$block = true
# 						$start = $index
# 					end
# 				end

# 				# przechwyć tekst komentarza w linii
# 				if $single then
# 					# w przypadku gdy komentarze liniowe występują jeden pod drugim, połącz je w jeden
# 					if $lastidx == index - 1 then
# 						$comments[$comments.length - 1].Text << "\n" << CommentArtifactRemove( line[($index + 2)..-1].strip, 1 )
# 						$comments[$comments.length - 1].End  = index 
# 						$comments[$comments.length - 1].Type = 2
# 					else
# 						$lastidx = index
# 						$comments.push( Struct::Comment.new(
# 							index,
# 							index,
# 							-1,
# 							1,
# 							CommentArtifactRemove( line[($index + 2)..-1].strip, 1 )
# 						) )
# 					end
# 					$single = false
# 					break
# 				end

# 				# szukaj zakończenia komentarza blokowego lub komentarza w dalszej części linii
# 				$index = line.index( '/', $index + 1 )

# 				# początek komentarza blokowego, kopiuj do końca
# 				if $start != -1 && $block && $index == nil then
# 					$comments.push( Struct::Comment.new(
# 						index,
# 						index,
# 						-1,
# 						3,
# 						CommentArtifactRemove( line[($start + 2)..-1].strip, 3 )
# 					) )
# 					break
# 				end
# 			end

# 			# dodaj linie do komentarza blokowego
# 			if $start == -1 && $block then
# 				$comments[$comments.length - 1].Text << "\n" << CommentArtifactRemove( line.strip, 4 )
# 			end
# 		end

# 		return $comments
# 	end

# 	# =============================================================================================
# 	# Usuwa artefakty z komentarzy.
# 	# Przykładem artefaktu jest * powtarzająca się w każdej kolejnej linii komentarza blokowego.
# 	# 
# 	# Dostępne typy komentarzy:
# 	#   1. Komentarz liniowy
# 	#   2. Komentarz blokowy w linii
# 	#   3. Początek komentarza blokowego
# 	#   4. Komentarz blokowy
# 	#   5. Koniec komentarza blokowego
# 	#   
# 	# Artefakty powinny dotyczyć również znaków specjalnych.
# 	# 
# 	# Params:
# 	#     line: Linia komentarza do sprawdzenia.
# 	#     type: Typ komentarza.
# 	#     
# 	# Returns:
# 	#     Tekst po usunięciu artefaktów z komentarzy.
# 	# =============================================================================================
# 	def self.CommentArtifactRemove( line, type )
# 		#komentarz w linii
# 		if type == 1 then

# 		# komentarz blokowy
# 		else
# 			if line.length == 1 && line[0] == '*' then
# 				return ''
# 			elsif line[0] == '*' && line[1] == ' ' then
# 				return line.sub('* ', '')
# 			end
# 		end
# 		# jeżeli nic nie zostało dopasowane zwróć tekst
# 		return line
# 	end

# end

# comments = Gramma.GetAllComments( "../test/main.c" )

# gramma = Gramma.new
# gramma.parse_file( '../test/main.c' )

# #comments = Gramma.getAllComments( "../test/main.c" )

# File.open( 'test.html', 'w' ) do |file|
# 	comments.each do |elem|
# 		file.write( "\n\n" )
# 		file.write( elem )
# 	end
# end

end