module Zaskroniec

	ZCG_PARSE_NONE    = 0x00    # tryb oczekiwania parsera
	ZCG_PARSE_COMMENT = 0x01    # pobieranie komentarza
	ZCG_PARSE_ELEMENT = 0x02    # pobieranie nazwy elementu (zmienna / reguła)
	ZCG_PARSE_INNER   = 0x04    # pobieranie zawartości elementu (zmienna / reguła)

	# 
	# Struktura nowej reguły gramatyki.
	# Reguły w pliku zaznaczane są symbolem '@'.
	# 
	Struct.new(
		"GrammaRule",
		:Name,          # nazwa reguły
		:Extract,       # wypakowanie danych pobranych wyrażeniem regulanym
		:Replace,       # zastąpienie pobranych danych inną wartością
		:Scope,         # zakres względem którego działa reguła
		:First,         # znak do rywalizacji pomiędzy kolejnymi regułami - kto pierwszy ten lepszy
		:Regex          # wyrażenie regularne względem którego pobierane są dane
	)

	#
	# Klasa zawierająca funkce do wczytywania danych gramatyki.
	# Dane zapisywane są głównie do plików ".zlr" (Zaskroniec Language Rules).
	# Plik ten może być odczytany a jego zawartość przetworzona bezpośrednio przez tą klasę.
	# Klasa umożliwia również samodzielne ustawianie zmiennych i reguł.
	# 
	# Plik z regułami powinien wyglądać następująco:
	# 
	# % Wszystkie zmienne powinny być pisane wielkimi literami.
	# % Tylko zmienne specjalne deklarowane są przez system małymi literami.
	# 
	# @VARIABLE
	#     Treść zmiennej.
	#     Znaki nowej linii są pomijane a znaki spacji z przodu czyszczone.
	#     Znaki spacji wpisane za ostatnim znakiem przed znakiem nowej linii są uwzględniane.
	# 
	# % Tak wpisuje się komentarz.
	# % Istnieją tylko liniowe komentarze.
	# % Plik z regułami zawiera szereg tagów, które może uwzględniać.
	# % Komentarza nie można napisać po wpasaniu jakiegokolwiek znaku.
	# % Nie można więc zrobić czegoś takiego:
	# %    \SCOPE GLOBAL    % część globalna
	# % Znaki następujące po słowie GLOBAL w takim przypadku będą uwzględniane do wartości reuły.
	# % Poniższa reguła zamienia wszystkie elementy znajdujące się pomiędzy znakami *(...)* na
	# % znaki <!--(...)-->
	# 
	# #RULE
	#     \SCOPE   GLOBAL
	#     \FIRST   *
	#     \REPLACE <!--%{value}-->
	#     \EXTRACT
	#     \REGEX   *([^*]+)*
	#
	# Reguły opisane są przy funkcji parsującej reguły.
	#
	class Gramma

		# Zmienne do odczytu z zewnątrz
		attr_reader :rules, :vars

		# 
		# Funkcja inicjalizująca dane w klasie.
		# Wywoływana przy tworzeniu klasy po podaniu słowa kluczowego new.
		# 
		def initialize
			@rules = {}
			@vars  = {}
			@elems = {}
		end

		#
		# Tworzy pustą regułę o podanej nazwie.
		# 
		# PARAMETERS:
		#     name - Nazwa reguły do utworzenia.
		#
		def empty_rule( name )
			return Struct::GrammaRule.new(
				name,
				false,
				nil,
				nil,
				nil,
				nil
			)
		end

		#
		# Dodaje nową regułę do listy.
		# 
		# PARAMETERS:
		#     rule - Nowa reguła do dodania.
		#
		def set_rule( rule )
			@rules[rule.Name] = rule
		end

		#
		# Dodaje nową zmienną do listy.
		#
		# PARAMETERS:
		#     name  = Nazwa zmiennej.
		#     value = Wartość zmiennej do podmiany.
		#
		def set_variable( name, value )
			@vars[name] = value
		end

		#
		# Przetwarza ciąg znaków w poszukiwaniu reguł i zmiennych.
		# 
		# PARAMETERS:
		#     str = Ciąg znaków do przetworzenia.
		# 
		def get_rules_from_string( str )

			parse_elements( str )
			parse_variables()
			parse_rules()

		end

		#
		# Przetwarza zawartość pliku w poszukiwaniu reguł i zmiennych.
		# 
		# PARAMETERS:
		#     file = Nazwa pliku do sprawdzenia.
		#
		def get_rules_from_file( file )

			data = File.read( file )
			get_rules_from_string( data )

		end

	protected

		#
		# Wyodrębnia znane elementy z ciągu znaków.
		# 
		# Wszystkie elementy pakowane są do jednej tablicy, funkcja nie rozróżnia
		# aktualnie i nie dzieli elementów na zmienne i reguły.
		# Podział ten możliwy jest po wywołaniu funkcji rozpoznających te elementy.
		# 
		# Funkcja wyszukuje elementów poprzedzonych znakami:
		# #, @ oraz %, gdzie # to reguła, @ to zmienna oraz % to komentarz.
		# Komentarze są pomijane.
		#
		# PARAMETERS:
		#      data = Ciąg znaków do przetworzenia.
		#
		def parse_elements( data )

			start = true
			white = true
			parse = ZCG_PARSE_NONE
			key   = ''
			index = 0
			krule = false

			# przetwarzaj cały plik i wyodrębnij z niego elementy
			data.each_char.with_index do |char, char_index|

				# wykryj czy na początku znajdują się białe znaki
				if( start && char == ' ' || char == "\t" ) then
					white = true
					start = false

				# pomijaj dalsze spacje i tabulatory od początku
				elsif( white && char == ' ' || char == "\t" ) then
					next
				end

				# wykryj komentarz w tekście i go pomiń
				if( char == '%' && white ) then

					# komentarz zawsze posiada po znaku co najmniej jedną spację
					if( data[char_index + 1] == ' ' ) then
						parse |= ZCG_PARSE_COMMENT
					end

				# wykryj zmienną lub regułę w pliku z regułami
				elsif( (char == '@' || char == '#') && start ) then

					key   = ''
					krule = char == '#'
					parse = ZCG_PARSE_ELEMENT

				# w przypadku znaku nowej linii sprawdź co jest dalej
				elsif( char == "\n" || char == "\r" ) then
					start = true
					white = true

					if( parse == ZCG_PARSE_INNER && krule ) then
						@elems[key] << char
					end

					# nie powtarzaj tego kroku przy zakończeniu linii typu \r\n
					if( char == "\n" && data[char_index - 1] == "\r" ) then
						next
					end

					# sprawdź czy kolejnym znakiem po nowej linii jest znak spacji lub tabulator
					nextsot = (data[char_index + 1] == ' '  || data[char_index + 1] == "\t") ||
							 ((data[char_index + 2] == ' '  || data[char_index + 2] == "\t") &&
							   data[char_index]     == "\r" && data[char_index + 1] == "\n")

					# przy nowej linii zakończ parsowanie
					if( (parse & ZCG_PARSE_COMMENT) > 0 ) then
						parse &= ~ZCG_PARSE_COMMENT

					# w przypadku parsowania zmiennej, przejdź do parsowania jej treści
					elsif( parse == ZCG_PARSE_ELEMENT ) then
						@elems[key] = ''
						parse = ZCG_PARSE_INNER if nextsot
					end

					next

				# gdy nic nie zostanie dopasowane a podany zostanie znak odstępu na początku, pomiń
				elsif( white && (char == ' ' || char == "\t") ) then
					next
				end

				white = false
				start = false

				# pomijaj komentarze w pliku z regułami
				if( (parse & ZCG_PARSE_COMMENT > 0) ) then
					next

				# pobieraj nazwę zmiennych i reguł
				elsif( parse == ZCG_PARSE_ELEMENT ) then
					key << char
					next

				# pobieraj zawartość zmiennych i reguł
				elsif( parse == ZCG_PARSE_INNER ) then
					@elems[key] << char
					next
				end
			end

		end

		#
		# Wyszukuje i podmienia zmienne w wybranym elemencie.
		# 
		# Zmienne w pliku definiowane są przez ciąg @NAZWA i wywoływane poprzez ${NAZWA}
		# Wartość zmiennej to wszystko co zostało wpisane po ciągu @NAZWA do napotkania kolejnego
		# elementu lub napotkania znaku końca pliku.
		# 
		# Podmiana zmiennych w zmiennych musi wystąpić w odpowiedniej kolejności.
		# W przeciwnym wypadku zmienne nie zostaną podmienione.
		#
		# PARAMETERS:
		#     elem = Element w którym zmienne mają być podmieniane.
		#
		def replace_variables( elem )

			# szukaj zmiennych w zmiennej
			results = []
			elem.scan( /\$\{([A-Z0-9_]+)\}/ ) do |smatch|
				results << [smatch[0], $~.offset(0)[0], $~.offset(0)[1]]
			end

			if( results.length > 0 ) then
				rindex = results.length - 1

				while( rindex >= 0 ) do
					# jeżeli zmienna nie została zdefiniowana, pozostaw tekst
					if( !@vars.key?(results[rindex][0]) ) then
						next
					end

					# w przeciwnym wypadku podmień go
					elem[results[rindex][1]..results[rindex][2]] = @vars[results[rindex][0]]
					rindex -= 1
				end
			end

		end

		# 
		# Pobiera zmienne z przetworzonych wcześniej elementów w tekście.
		# 
		# Każda zmienna jest parsowana w poszukiwaniu odnośników do innych zmiennych.
		# Ważna jest oczywiście kolejność deklarowania zmiennych i odnoszeniu się do nich.
		# Nie można więc napisać:
		# 
		# @VAR
		#     This variable has: ${NEXTVAR}
		# @NEXTVAR
		#     Nextvar
		# 
		# Aby ten przykład działał poprawnie, przed zmienną VAR powinna być deklarowana
		# zmienna NEXTVAR.
		# 
		def parse_variables()

			# pobierz nazwy zmiennych i ich zawartość
			@elems.each do |key, value|
				if( key[0] == '@' ) then
					@vars[key[1..-1].strip] = value
				end
			end

			# podmień zmienne gdy istnieją
			@vars.each do |key, value|
				replace_variables( @vars[key] )
			end

		end

		#
		# Pobiera reduły z przetworzonych wcześniej elementów w tekście.
		# 
		# Działa na podobnej zasadzie jak parser zmiennych z tą różnicą, że zawartość
		# reguł poddawana jest dodatkowemu procesowi parsowania aby wyciągnąć specjalne
		# znaczniki z danych.
		# 
		# W przypadku reguł nie ma znaczenia czy podana zmienna została zadeklarowana przed
		# czy po jej użyciu, gdyż reguły są przetwarzane dopiero po zmiennych.
		# 
		# Zawartość reguły wygląda następująco:
		# @RULE
		#     \SCOPE   <- przestrzeń w której reguła ma obowiązywać
		#     \FIRST   <- znak rywalizacji o pierwszeństwo
		#     \REPLACE <- zamiana znalezionego ciągu na inny ciąg znaków
		#     \EXTRACT <- wypakowanie danych do późniejszej obróbki
		#     \REGEX   <- wyrażenie regularne do wyszukiwania danych dla reguły
		#
		# Gdzie żaden z wymienionych elementów nie musi być podany.
		# Przestrzeń nazw to inaczej nazwa reguły.
		# Standardowo istnieją dwie przestrzenie nazw - GLOBAL oraz ALL, gdzie ALL oznacza
		# przetwarzanie we wszystkich dostępnych przestrzeniach, a GLOBAL tylko w elementach
		# które nie są przypisane do żadnej przestrzeni.
		#
		def parse_rules()

			# przetwarzaj wszystkie dostępne reguły
			@elems.each do |key, value|

				if( key[0] == '#' ) then
					key = key[1..-1].strip
					@rules[key] = empty_rule( key )

					value.scan( /\\([A-Z0-9_]+)[\s]*([^\r\n]*)/ ) do |smatch|

						# przestrzeń w jakiej obowiązuje reguła
						if( smatch[0] == "SCOPE" ) then
							@rules[key].Scope = smatch[1].split(' ')

						# znak do rywalizacji o pierwszeństwo
						elsif( smatch[0] == "FIRST" ) then
							@rules[key].First = smatch[1].strip

						# zamiana dopasowanego ciągu na inny ciąg
						elsif( smatch[0] == "REPLACE" ) then
							@rules[key].Replace = smatch[1].strip

						# wyodrębnienie oryginalnego ciągu do zmiennej
						elsif( smatch[0] == "EXTRACT" ) then
							@rules[key].Extract = true

						# wyrażenie regularne dopasowujące wybrany element
						elsif( smatch[0] == "REGEX" ) then
							@rules[key].Regex = smatch[1].strip
							replace_variables( @rules[key].Regex )
						end
					end
				end
			end
		end

	end

end
