<?php
require_once( "./logger.php" );
require_once( "./yaml/Spyc.php" );

class GroupElement
{
	public $content;
	public $start;
	public $startLine;
	public $end;
	public $endLine;
	public $subGroups;

	public function __construct()
	{
		$this->content   = "";
		$this->start     = 0;
		$this->end       = 0;
		$this->subGroups = [];
		$this->startLine = 0;
		$this->endLine   = 0;
	}
}

class GroupData
{
	public $name;
	public $group;
	public $pos;
	public $parent;

	public function __construct()
	{
		$this->name   = '';
		$this->parent = null;
		$this->group  = null;
		$this->pos    = 0;
	}
}

class Berseker
{
	public $file = '';
	public $data = '';
	public $length = 0;
	public $pos = 0;

	public $rules = [];
	public $results = [];
	public $storage = [];
	public $groups = [];
	public $gpos = 0;
	public $gcontent = [];
	public $linefeeds = [];
	public $lfpos = 0;
	public $lfcpos = 0;
	public $lastgpos = 0;

	public $mappings = [
		'REWIND'               => [[            ], 'Rewind',            1, [-1            ]],
		'LOOP'                 => [[            ], 'Loop',              1, [-1            ]],
		'EXECUTE'              => [[            ], 'Execute',           1, [-1            ]],
		'SEEK'                 => [[false       ], 'Seek',              2, [-1,  0        ]],
		'SEEK_GET'             => [[true        ], 'Seek',              2, [-1,  0        ]],
		'SEEK_UNTIL'           => [[false       ], 'SeekUntil',         3, [-1, -1,  0    ]],
		'SEEK_UNTIL_ESCAPE'    => [[            ], 'SeekUntil',         3, [-1, -1, -1    ]],
		'SEEK_UNTIL_ONE_OF'    => [[            ], 'SeekUntilOneOf',    2, [-1, -1,       ]],
		'SEEK_BLOCK'           => [[false       ], 'SeekBlock',         3, [-1, -1,  0    ]],
		'SEEK_BLOCK_GET'       => [[true        ], 'SeekBlock',         3, [-1, -1,  0    ]],
		'CHAR_CHECK'           => [[true        ], 'Char',              3, [-1, -1,  0    ]],
		'CHAR'                 => [[false       ], 'Char',              3, [-1, -1,  0    ]],
		'STRING'               => [[false       ], 'String',            4, [-1, -1, -1,  0]],
		'STRING_CHECK'         => [[true        ], 'String',            4, [-1, -1, -1,  0]],
		'GROUP'                => [[            ], 'Group',             1, [-1            ]],
		'STORAGE'              => [[            ], 'Storage',           1, [-1            ]],
		'REPLACE'              => [[  1         ], 'Replace',           3, [ 0, -1, -1    ]],
		'REPLACE_MANY'         => [[            ], 'Replace',           3, [-1, -1, -1,   ]],
		'REPLACE_UNTIL'        => [[true        ], 'ReplaceUntil',      4, [-1, -1,  0, -1]],
		'REPLACE_UNTIL_ESCAPE' => [[            ], 'ReplaceUntil',      4, [-1, -1, -1, -1]],
		'RUN_PREPROCESSOR'     => [[            ], 'RunPreprocessor',   0, [              ]], 
		'REPLACE_UNTIL_ONE_OF' => [[            ], 'ReplaceUntilOneOf', 3, [-1, -1, -1    ]],
		'NEXT'                 => [[false       ], 'Next',              4, [-1, -1, -1,  0]],
		'NEXT_CHECK'           => [[true        ], 'Next',              4, [-1, -1, -1,  0]],
		'WRITE'                => [[            ], 'Write',             1, [-1            ]],
		'WRITE_FROM_STREAM'    => [[false       ], 'Write',             1, [ 0            ]],
		'WHITECHAR'            => [[false, false], 'WhiteChar',         2, [ 0,  0        ]],
		'WHITECHAR_ESCAPE'     => [[false, true ], 'WhiteChar',         2, [ 0,  0        ]],
		'WHITESPACE'           => [[true,  true ], 'WhiteChar',         2, [ 0,  0        ]]
	];

	public function __construct( string $file )
	{
		$this->data = file_get_contents( $file );

		if( $this->data === false )
			exit;

		$this->file   = $file;
		$this->length = strlen( $this->data );
		$this->pos    = 0;
		$this->gpos   = 0;

		// wskaźnik na główną strukturę
		$this->groups[] = new GroupData( '', null, 0 );
		$this->groups[0]->parent = &$this->storage;
		$this->groups[] = new GroupData( '', null, 0 );
		$this->groups[1]->parent = &$this->results;

		$this->rules = [];


			// // reguła wejściowa
			// 'entry' => [
			// 	5,
			// 	// pomija białe znaki
			// 	['whitespace', true],
			// 	// porównuje znak i przechodzi do reguły podanej po znaku
			// 	// ta wersja kontynuuje działanie reguły po wykonaniu makra
			// 	['echar', 
			// 		[
			// 			'#' => 'macro',
			// 			'/' => 'comment'
			// 		],
			// 		// reguła wykonywana w przeciwnym razie lub false
			// 		'symbol'
			// 	],
			// 	// przechodzi do podanej reguły
			// 	['goto', 'entry']
			// ],

			// 'macro' => [
			// 	3,
			// 	['whitespace', true],
			// 	// porównuje pobrany ciąg znaków z podanymi i przechodzi do reguły podanej po ciągu
			// 	['string', 
			// 		// regex używany do pobrania ciągu z tekstu
			// 		'/[a-z]+/A',
			// 		[
			// 			'include' => 'macro_include',
			// 			'define' => 'macro_define'
			// 		],
			// 		// reguła wykonywana w przeciwnym razie lub false
			// 		'skip_line'
			// 	]
			// ],
			
			// 'macro_include' => [
			// 	3,
			// 	['whitespace', true],
			// 	// to samo co echar z tą różnicą że po wykonaniu reguły obecna jest przerywana
			// 	['char',
			// 		[
			// 			'<' => 'macro_include_file',
			// 			'"' => 'macro_include_file'
			// 		],
			// 		'skip_line'
			// 	]
			// ],

			// 'macro_include_file' => [
			// 	3,
			// 	// zapisuje do kolekcji pobrany ciąg
			// 	['capture',
			// 		'/[^">\r\n]+/A',
			// 		// w przypadku gdy program nie znajdzie dopasowania, wykonana zostanie podana tutaj reguła
			// 		// false nie wykonuje żadnej reguły
			// 		false
			// 	],
			// 	['goto', 'skip_line']
			// ],

			// 'macro_define' => [
			// 	4,
			// 	['whitespace', true],
			// 	['capture',
			// 		'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
			// 		false
			// 	],
			// 	// sprawdza czy następny znak jest równy podanemu i wykonuje jedną z podanych
			// 	// reguł w zależności czy sprawdzenie się powiodło czy nie
			// 	['next',
			// 		'(',
			// 		'macro_define_param',
			// 		'skip_line'
			// 	]
			// ],

			// 'macro_define_param' => [
			// 	5,
			// 	['whitespace', true],
			// 	['capture',
			// 		'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
			// 		false
			// 	],
			// 	['whitespace', true],
			// 	['next',
			// 		',',
			// 		'macro_define_param',
			// 		'skip_line'
			// 	]
			// ],

			// // pomijanie linii
			// 'skip_line' => [
			// 	4,
			// 	// pomija dane aż do napotkania jednego z podanych znaków
			// 	// tru pobiera znak, false pozostawia (uskip - until skip)
			// 	['uskip', [
			// 		'\\' => false,
			// 		"\r" => false,
			// 		"\n" => false
			// 	]],
			// 	// przerywa dalsze działanie gdy znak nie jest równy podanemu
			// 	// gdy jest, pobiera go (ibchar - inverse break char)
			// 	['ibchar', '\\'],
			// 	// jeżeli kolejnym znakiem jest znak nowej linii, to goto skip_line
			// 	['char',
			// 		[
			// 			"\r" => 'skip_line',
			// 			"\n" => 'skip_line'
			// 		],
			// 		false
			// 	]
			// ],

			// 'symbol' => [
			// 	5,
			// 	['whitespace', true],
			// 	['capture',
			// 		'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
			// 		'symbol_skip'
			// 	],
			// 	['whitespace', true],
			// 	['char',
			// 		[
			// 			'(' => 'symbol_params',
			// 			';' => false,
			// 			'=' => 'symbol_skip',
			// 			'{' => 'symbol_inner_symbol'
			// 		],
			// 		'symbol'
			// 	]
			// ],

			// 'symbol_inner_symbol' => [
			// 	5,
			// 	['whitespace', true],
			// 	['capture',
			// 		'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
			// 		'symbol_skip'
			// 	],
			// 	['whitespace', true],
			// 	['char',
			// 		[
			// 			'*' => 'symbol_inner_pointer',
			// 			';' => 'symbol_inner_symbol'
			// 		],
			// 		'symbol_inner_symbol'
			// 	]
			// ],

			// 'symbol_skip' => [
			// 	3,
			// 	['uskip', [
			// 		';' => false,
			// 		',' => false,
			// 		'{' => false
			// 	]],
			// 	['char',
			// 		[
			// 			',' => false,
			// 			';' => false,
			// 			'{' => false
			// 		],
			// 		false
			// 	]
			// ],

			// 'symbol_body_recognize' => [
			// 	3,
			// 	['whitespace', true],
			// 	['next',
			// 		'{',
			// 		'symbol_body_skip',
			// 		'symbol_skip'
			// 	]
			// ],

			// 'symbol_body_skip' => [
			// 	2,
			// 	// deep skip
			// 	// pomijanie znaków do napotkania najbardziej zewnętrznego znaku zamykającego obszar
			// 	// porównuje poziomy i zamyka w odpowiednim momencie
			// 	['dskip', '{', '}']
			// ],

			// 'symbol_params' => [
			// 	5,
			// 	['whitespace', true],
			// 	['char',
			// 		[
			// 			'*' => 'symbol_pointer',
			// 			',' => 'symbol_params',
			// 			')' => 'symbol_body_recognize'
			// 		],
			// 		false
			// 	],
			// 	['capture',
			// 		'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
			// 		'symbol_skip'
			// 	],
			// 	['goto', 'symbol_params']
			// ],

			// 'symbol_pointer' => [
			// 	2,
			// 	['goto', 'symbol_params']
			// ],

			// 'symbol_inner_pointer' => [
			// 	2,
			// 	['goto', 'symbol_inner_symbol']
			// ]
	}

	/**
	 * Przewija strumień do podanej pozycji.
	 *
	 * DESCRIPTION:
	 *     Funkcja zmienia pozycję kursora strumienia danych traktując wartość 0 jako pierwszy znak.
	 *     Podanie wartości ujemnej dla pozycji spowoduje przesunięcie kursora o podaną
	 *     ilość znaków licząc od końca.
	 *
	 * CODE:
	 *     $this->data = "Chrząszcz brzmi w trzcinie w Szczebrzeszynie.";
	 *
	 *     // przesuwa kursor na znak "ą" (5 od początku)
	 *     $this->Rewind( ['', 4] );
	 *     // przesuwa kursor na znak "r" (10 od końca)
	 *     $this->Rewind( ['', -10] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Nowa pozycja kursora.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function Rewind( array &$rule ): bool
	{
		$this->pos = $rule[1] < 0
			? $this->pos + $rule[1]
			: $rule[1];

		// Logger::Log( "  - rewind caret to: {$this->pos}" );
		return true;
	}

	/**
	 * Tworzy pętlę powtarzającą regułę.
	 *
	 * DESCRIPTION:
	 *     Tworzy pętlę w której wywołuje funkcje z podanej reguły.
	 *     Pętla jest przerywana gdy podczas działania którakolwiek z funkcji zwróci wartość FALSE.
	 *
	 * CODE:
	 *     $this->Loop( ['', 'RULE_TO_LOOP'] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Nazwa reguły do powtarzania w pętli.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function Loop( array &$rule ): bool
	{
		$isrun = true;
		$lrule = &$this->rules[$rule[1]];

		while( $isrun )
		{
			// Logger::Log( "> {$rule[1]}" );

			// sprawdź czy nie przekroczono zakresu ciągu
			if( $this->length <= $this->pos )
				break;
			// Logger::IndentInc();

			// wywołuj operacje zapisane w regule
			foreach( $lrule as $val )
			{
				// Logger::Log( "+ {$val[0]}" );
				if( !$this->{$val[0]}($val) )
				{
					$isrun = false;
					break;
				}
			}

			// Logger::IndentDec();
		}
		return true;
	}

	/**
	 * Wywołuje regułę podaną w parametrze.
	 *
	 * CODE:
	 *     $this->Execute( ['', 'SECTION_TO_EXECUTE'] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Nazwa reguły do wykonania.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function Execute( &$rule ): bool
	{
		$this->Run( $rule[1] );
		return true;
	}

	/**
	 * Przesuwa kursor na dane o podaną ilość pól.
	 *
	 * DESCRIPTION:
	 *     Kursor można przesuwać zarówno do przodu jak i do tyłu w zależności od tego czy wartość
	 *     przesunięcia została podana jako liczba dodatnia czy ujemna.
	 *
	 * CODE:
	 *     $this->data = "Chrząszcz brzmi w trzcinie w Szczebrzeszynie.";
	 *
	 *     // przesuwa kursor o 15 pól do przodu, pobierając dane (do wartości "i")
	 *     $this->Seek( ['', 14, true] );
	 *     // cofa kursor o dwa pola o wartości "z" bez pobierania danych
	 *     $this->Seek( ['', -2, false] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Wartość o jaką przesunięty ma być kursor względem aktualnej pozycji.
	 *           - 2 > Czy dane mają być pobierane?
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function Seek( array &$rule ): bool
	{
		// Logger::Log(
			// "  - [{$rule[1]} : \"" .
				// $this->ReadableChar($this->data[$this->pos + $rule[1]]) .
				// "\"] from [{$this->pos} : \"" .
				// $this->ReadableChar($this->data[$this->pos]) .
			// "\"]"
		// );

		// zapis danych do grupy
		if( $rule[2] )
		{
			// Logger::Log( "  - save data to current group" );
			if( $rule[1] > 0 )
				$this->gcontent[$this->gpos] .= substr( $this->data, $this->pos, $rule[1] );
			else
				$this->gcontent[$this->gpos] .= substr( $this->data, $this->pos - $rule[1], -$rule[1] );
		}

		// przesunięcie
		$this->pos += $rule[1];
		return true;
	}

	/**
	 * Przesuwa kursor aż do napotkania podanego znaku.
	 *
	 * DESCRIPTION:
	 *     W odróżnieniu od zwykłej funkcji Seek, ta przewija dane do napotkania podanego znaku.
	 *     Gdy funkcja przekroczy zakres zwraca wartość FALSE.
	 *     Ta wersja może przewijać kursor tylko do przodu.
	 *     Do funkcji można dodatkowo przekazać znak ucieczki, który podany przed znakiem do którego
	 *     funkcja ma przetwarzać dane powoduje pominięcie go i szukanie kolejnego znaku.
	 *
	 * CODE:
	 *     $this->data = "Chrząszcz brzmi\| w| trzcinie w Szczebrzeszynie.";
	 * 
	 *     // pomija znaki do napotkania znaku "|"
	 *     $this->SeekUntil( ['', '|', false, false] );
	 *     $this->Rewind   ( ['', 0] );
	 *     // pomija znaki aż do napotkania znaku "|" z czego będzie on się znajdował po znaku "w"
	 *     // gdyż pierwszy po słowie "brzmi" zawiera przed sobą znak ucieczki
	 *     $this->SeekUntil( ['', '|', '\\', false] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Znak do której dane mają być przewijane.
	 *           - 2 > Znak ucieczki, lub FALSE.
	 *           - 3 > Czy dane mają być pobierane?
	 *
	 * RETURNS:
	 *     Wartość FALSE gdy kursor wyjdzie poza zakres danych, w przeciwnym wypadku TRUE.
	 */
	protected function SeekUntil( array &$rule ): bool
	{
		// Logger::Log('  - seek until "' . $this->ReadableChar($rule[1]) . '"' );

		// przewijaj i pobieraj znaki
		if( $rule[3] )
			while( $this->length > $this->pos && $this->data[$this->pos] != $rule[1] )
			{
				if( $this->data[$this->pos] == $rule[2] && $this->data[$this->pos] == $rule[1] )
					$this->pos++;
				$this->gcontent[$this->gpos] .= $this->data[$this->pos++];
			}
		// tylko przewijaj
		else
			while( $this->length > $this->pos && $this->data[$this->pos] != $rule[1] )
			{
				if( $this->data[$this->pos] == $rule[2] && $this->data[$this->pos] == $rule[1] )
					$this->pos++;
				$this->pos++;
			}
		$this->pos++;

		if( $this->length <= $this->pos )
			return false;

		return true;
	}

	/**
	 * Przewija kursor do napotkania jednego z podanych znaków.
	 *
	 * DESCRIPTION:
	 *     W odróżnieniu od zwykłej funkcji SeekUntil, ta przewija dane do napotkania jednego z
	 *     podanych w tablicy znaków.
	 *     Gdy funkcja przekroczy zakres zwraca wartość FALSE.
	 *     Ta wersja funkcji może przewijać kursor tylko do przodu.
	 *     Tablica zawierająca znaki stopu przyjmuje dwie wartości - TRUE lub FALSE w zależności od tego,
	 *     czy znak ma zostać pobrany po zatrzymaniu pętli czy też nie.
	 *
	 * CODE:
	 *     $this->data = "Chrząszcz brzmi. w| trzcinie w Szczebrzeszynie.";
	 * 
	 *     // przewija i pobiera znaki aż do napotkania jednego z podanych znaków
	 *     // zatrzymuje się po słowie brzmi (trafia na kropkę) i konsumuje znak ze strumienia
	 *     $this->SeekUntilOneOf( ['', [
	 *             '.' => true,
	 *             '|' => false
	 *         ],
	 *         true
	 *     ] );
	 *     // szuka dalej, trafia na znak "|" zaraz po znaku "w"
	 *     $this->SeekUntilOneOf( ['', [
	 *             '.' => true,
	 *             '|' => false
	 *         ],
	 *         false
	 *     ] );
	 *     // jako że znak nie jest konsumowany, kursor trzeba przesunąć samodzielnie
	 *     $this->Seek( ['', 1] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Tablica znaków do których dane będą przesuwane.
	 *           - 2 > Czy dane mają być pobierane?
	 *
	 * RETURNS:
	 *     Wartość FALSE gdy kursor wyjdzie poza zakres danych, w przeciwnym wypadku TRUE.
	 */
	protected function SeekUntilOneOf( array &$rule ): bool
	{
		// Logger::Log('  - seek until one of [' . $this->ReadableCharArray($rule[1]) . ']' );

		// przewijaj i pobieraj znaki
		if( $rule[2] )
			while( $this->length > $this->pos && !isset($rule[1][$this->data[$this->pos]]) )
				$this->gcontent[$this->gpos] .= $this->data[$this->pos++];
		// tylko przewijaj
		else
			while( $this->length > $this->pos && !isset($rule[1][$this->data[$this->pos]]) )
				$this->pos++;

		// pobierz ostatni znak gdy tak zostało podane w regule
		if( $this->length > $this->pos && $rule[1][$this->data[$this->pos]] )
			$this->pos++;

		if( $this->length <= $this->pos )
			return false;

		return true;
	}

	/**
	 * Przewija kursor do końca bloku.
	 *
	 * DESCRIPTION:
	 *     Ta wersja funkcji pozwala na przesunięcie kursora za blok w którym się znajduje.
	 *     W odróżnieniu od zwykłej funkcji SeekUntil, ta uwzględnia zagęszczenie bloków.
	 *     Dzięki temu pomija zagęszczone bloki i szuka ostatniego możliwego wystąpienia znaku końca bloku.
	 *     Przewijanie odbywa się tylko do przodu.
	 *     Gdy funkcja przekroczy zakres danych zwraca wartość FALSE.
	 *
	 * CODE:
	 *     $this->data = "{ if(x) { return x; } else { return y; } } return z;"
	 *
	 *     // przesuń o 1, gdyż kursor musi być wewnątrz bloku
	 *     $this->Seek( ['', 1, false] );
	 *     // szukaj końca bloku, tutaj jest to pozycja zaraz przed "return z"
	 *     $this->SeekBlock( ['', '{', '}', false] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Znak rozpoczęcia bloku.
	 *           - 2 > Znak zakończenia bloku.
	 *           - 3 > Czy dane mają być pobierane?
	 *
	 * RETURNS:
	 *     Wartość TRUE gdy koniec bloku zostanie znaleziony, w przeciwnym wypadku FALSE.
	 */
	protected function SeekBlock( array &$rule ): bool
	{
		$data = &$this->data;
		$pos  = &$this->pos;
		$deep = 1;

		// przetwarzaj dopóki pozycja będzie w normie
		while( $this->length > $pos )
		{
			// blok wewnątrz bloku
			if( $data[$pos] == $rule[1] )
				$deep++;
			// blok zakończony
			else if( $data[$pos] == $rule[2] )
			{
				// zakończenie najbardziej zewnętrznego bloku przy pasujących znakach
				if( $deep == 1 )
				{
					$pos++;
					return true;
				}
				$deep--;
			}
			if( $rule[4] )
				$this->gcontent[$this->gpos] .= $this->data[$this->pos];
			$pos++;
		}
		return false;
	}

	/**
	 * Zamienia znaki na ten podany w parametrze reguły.
	 *
	 * DESCRIPTION:
	 *     Zamiana znaku powoduje jednocześnie przeunięcie kursora.
	 *     Ilość znaków do zamiany musi być wyrażona liczbą większą od zera.
	 *
	 * CODE:
	 *     $this->data = "Chrząszcz brzmi w trzcinie w Szczebrzeszynie.";
	 * 
	 *     // zamienia aktualny znak na znak "$" -> $rząszcz[...]
	 *     $this->Replace( ['', 1, '$', false] );
	 *     // zamienia cztery znaki na znak '#' i pobiera je do aktualnej grupy
	 *     // rezultat -> $####szcz[...]
	 *     $this->Replace( ['', 4, '#', true] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Ilość znaków do zamiany.
	 *           - 2 > Znak na który dane będą zamieniane.
	 *           - 3 > Czy dane mają być pobierane?
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function Replace( array &$rule ): bool
	{
		// Logger::Log(
			// "  - to [{$rule[1]} : \"" .
				// $this->ReadableChar($this->data[$this->pos + $rule[1]]) .
				// "\"] from [{$this->pos} : \"" .
				// $this->ReadableChar($this->data[$this->pos]) .
			// "\"]"
		// );

		// zapisz dane do grupy
		if( $rule[3] )
		{
			// Logger::Log( "  - save data to current group" );

			if( $rule[1] == 1 )
				$this->gcontent[$this->gpos] .= $this->data[$this->pos];
			else
				$this->gcontent[$this->gpos] .= substr( $this->data, $this->pos, $rule[1] );
		}

		// przesuń i zamieniaj znaki na te podane w argumencie
		// Logger::Log( "  - replace all values to: {$rule[2]}" );

		if( $rule[1] == 1 )
			$this->data[$this->pos++] = $rule[2];
		else
			for( $x = $this->pos + $rule[1]; $this->pos < $x; ++$this->pos )
				$this->data[$this->pos] = $rule[2];

		return true;
	}

	/**
	 * Zamienia dane w strumieniu aż do napotkania podanego znaku.
	 *
	 * DESCRIPTION:
	 *     W odróżnieniu od zwykłego Replace, ta funkcja zamienia dane aż do napotkania
	 *     podanego w argumencie znaku.
	 *     Gdy znak zostanie wykryty, funkcja zwraca wartość TRUE, zostawiając kursor
	 *     na wykrytym znaku.
	 *     Do funkcji można dodatkowo przekazać znak ucieczki, który podany przed znakiem do którego
	 *     funkcja ma przetwarzać dane powoduje pominięcie go i szukanie kolejnego znaku.
	 *
	 * CODE:
	 *     $this->data = "Chrząszcz brzmi\| w| trzcinie w Szczebrzeszynie.";
	 * 
	 *     // zamienia dane na znak "*" aż do napotkania znaku "|"
	 *     // rezultat: "****************| w| trzcinie"
	 *     $this->ReplaceUntil( ['', '|', '*', false, false] );
	 *     
	 *     // zamień ostatni znak "*" na "\"
	 *     // rezultat: "***************\| w| trzcinie"
	 *     $this->Seek   ( ['', -1, false] );
	 *     $this->Replace( ['', 1, '\\', false] );
	 *     $thiw->Rewind ( ['', 0] );
	 *
	 *     // zamienia dane na "-" aż do napotkania znaku "|" z czego będzie on się znajdował po
	 *     // dopiero znaku "w", gdyż pierwszy po słowie "brzmi" zawiera przed sobą znak ucieczki
	 *     // rezultat: "-------------------| trzcinie"
	 *     $this->ReplaceUntil( ['', '|', '-' '\\', false] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Znak do którego dane będą zamieniane.
	 *           - 2 > Znak na który dane będą zamieniane.
	 *           - 3 > Znak ucieczki, lub FALSE.
	 *           - 4 > Czy dane mają być pobierane?
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function ReplaceUntil( array &$rule ): bool
	{
		// Logger::Log( '  - "' . $this->ReadableChar($rule[2]) . '" untill "' .
			// $this->ReadableChar($rule[1]) . '"' );

		// pobieraj znaki i zamieniaj je
		if( $rule[4] )
		{
			// Logger::Log( '  - save data to current group' );

			while( $this->length > $this->pos && $this->data[$this->pos] != $rule[1] )
			{
				if( $this->data[$this->pos] == $rule[3] && $this->data[$this->pos] == $rule[1] )
					$this->pos++;

				$this->gcontent[$this->gpos] .= $this->data[$this->pos];
				$this->data[$this->pos++]     = $rule[2];
			}
		}
		// pomijaj znaki i zamieniaj je
		else
			while( $this->length > $this->pos && $this->data[$this->pos] != $rule[1] )
			{
				if( $this->data[$this->pos] == $rule[3] && $this->data[$this->pos] == $rule[1] )
					$this->pos++;

				$this->data[$this->pos++] = $rule[2];
			}
		$this->pos++;

		if( $this->length <= $this->pos )
			return false;

		return true;
	}

	/**
	 * Zamienia wszystkie znaki w strumieniu aż do napotkania jednego z podanych znaków.
	 *
	 * DESCRIPTION:
	 *     W odróżnieniu od ReplaceUntil ta funkcja zamienia dane aż do napotkania jednego z
	 *     podanych w argumencie znaków.
	 *     Tablica zawierająca znaki stopu przyjmuje dwie wartości - TRUE lub FALSE w zależności od tego,
	 *     czy znak ma zostać pobrany po zatrzymaniu pętli czy też nie.
	 *
	 * CODE:
	 *     $this->data = "Chrząszcz brzmi. w| trzcinie w Szczebrzeszynie.";
	 * 
	 *     // zamienia znaki na znak "@" aż do napotkania jednego z podanych znaków
	 *     // zatrzymuje się po słowie brzmi (trafia na kropkę) i konsumuje znak ze strumienia
	 *     // rezultat: "@@@@@@@@@@@@@@@. w| trzcinie[...]"
	 *     $this->ReplaceUntilOneOf( ['', [
	 *             '.' => true,
	 *             '|' => false
	 *         ],
	 *         '@'
	 *         true
	 *     ] );
	 *     // zamienia dalej, trafia na znak "|" zaraz po znaku "w"
	 *     // rezultat: "@@@@@@@@@@@@@@@.$$| trzcinie[...]"
	 *     $this->ReplaceUntilOneOf( ['', [
	 *             '.' => true,
	 *             '|' => false
	 *         ],
	 *         '$'
	 *         false
	 *     ] );
	 *     // jako że znak nie jest konsumowany, kursor trzeba przesunąć samodzielnie
	 *     $this->Seek( ['', 1] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Tablica znaków do których dane będą zamieniane.
	 *           - 2 > Znak na który dane będą zamieniane.
	 *           - 3 > Czy dane mają być pobierane?
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function ReplaceUntilOneOf( array &$rule ): bool
	{
		// Logger::Log( '  - "' . $this->ReadableChar($rule[2]) . '" untill one of [' .
			// $this->ReadableCharArray($rule[1]) . ']' );

		// pobieraj znaki i zamieniaj je
		if( $rule[3] )
		{
			while( !isset($rule[1][$this->data[$this->pos]]) )
			{
				$this->gcontent[$this->gpos] .= $this->data[$this->pos];
				$this->data[$this->pos++] = $rule[2];
			}

			if( $rule[1][$this->data[$this->pos]] )
				$this->pos++;
		}
		// tylko zamieniaj znaki
		else
		{
			while( !isset($rule[1][$this->data[$this->pos]]) )
				$this->data[$this->pos++] = $rule[2];

			if( $rule[1][$this->data[$this->pos]] )
				$this->pos++;
		}

		return true;
	}

	/**
	 * Zamienia dane w strumieniu do końca bloku.
	 *
	 * DESCRIPTION:
	 *     Ta wersja funkcji pozwala na zamianę danych na podany znak aż do zakończenia bloku.
	 *     W odróżnieniu od zwykłej funkcji ReplaceUntil, ta uwzględnia zagęszczenie znaków.
	 *     Dzięki temu pomija zagęszczone bloki i szuka ostatniego możliwego wystąpienia znaku końca bloku.
	 *     Gdy funkcja przekroczy zakres danych zwraca wartość FALSE.
	 *
	 * CODE:
	 *     $this->data = "{ if(x) { return x; } else { return y; } } return z;"
	 *
	 *     // przesuń o 1, gdyż kursor musi być wewnątrz bloku
	 *     $this->Seek( ['', 1, false] );
	 *     // zamieniaj dane aż do końca bloku
	 *     // rezultat: "{++++++++++++++++++++++++++++++++++++++++} return z;"
	 *     $this->ReplaceBlock( ['', '{', '}', '+' false] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Znak rozpoczęcia bloku.
	 *           - 2 > Znak zakończenia bloku.
	 *           - 3 > Znak na który dane mają być zamieniane.
	 *           - 4 > Czy dane mają być pobierane?
	 *
	 * RETURNS:
	 *     Wartość TRUE gdy koniec bloku zostanie znaleziony, w przeciwnym wypadku FALSE.
	 */
	protected function ReplaceBlock( array &$rule ): bool
	{
		$data = &$this->data;
		$pos  = &$this->pos;
		$deep = 1;

		// przetwarzaj dopóki pozycja będzie w normie
		while( $this->length > $pos )
		{
			// blok wewnątrz bloku
			if( $data[$pos] == $rule[1] )
				$deep++;
			// blok zakończony
			else if( $data[$pos] == $rule[2] )
			{
				// zakończenie najbardziej zewnętrznego bloku przy pasujących znakach
				if( $deep == 1 )
				{
					$pos++;
					return true;
				}
				$deep--;
			}
			if( $rule[4] )
				$this->gcontent[$this->gpos] .= $this->data[$this->pos];
			$this->data[$this->pos++] = $rule[3];
		}
		return false;
	}

	/**
	 * Pomija białe znaki występujące w tekście.
	 *
	 * DESCRIPTION:
	 *     Do białych znaków zaliczają się znaki nowej linii, spacji i tabulacji.
	 *     Funkcja pomija te znaki do napotkania znaku innego niż te wymienione powyżej.
	 *     Możliwe jest również użycie znaku ucieczki podczas pomijania bałych znaków.
	 *     Znak ucieczki stosowany jest np. w języku C.
	 *     Gdy znak ucieczki wykryty zostanie bezpośrednio przed znakiem nowej linii jest
	 *     traktowany jako biały znak.
	 *     Możliwe jest równeiż sprawdzanie tylko białych znaków oznaczających wcięcia i spacje,
	 *     bez znaków nowych linii.
	 *     W przypadku gdy w wyszukiwanym źródle znajdują się same białe znaki, funkcja zwróci
	 *     wartość FALSE.
	 *
	 * CODE:
	 *     // pomija białe znaki, traktując znak \ zaraz przed nową linią jako znak ucieczki
	 *     $this->WhiteChar( ['', false, '\\'] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Czy mają być sprawdzane tylko spacje i tabulacje?
	 *           - 2 > Znak ucieczki lub FALSE.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function WhiteChar( array &$rule ): bool
	{
		do
		{
			switch( $this->data[$this->pos] )
			{
				case ' ':
				case "\t":
					$this->pos++;
				break;
				case "\n":
				case "\r":
					if( $rule[1] )
						return true;
					$this->pos++;
				break;
				default:
					if( !$rule[1] && $this->data[$this->pos] == $rule[2] &&
							($this->data[$this->pos + 1] == "\n" || $this->data[$this->pos + 1] == "\r") )
						$this->pos += 2;
					else
						return true;
			}
		}
		while( $this->pos < $this->length );

		return false;
	}

	protected function Storage( array &$rule ): bool
	{
		if( $rule[1] )
		{
			// Logger::Log( "  - enter to temporary storage" );

			$this->lastgpos = $this->gpos;
			$this->gpos     = 0;

			$group = &$this->groups[0];
			$this->gcontent[0] = '';

			if( isset($group->parent[$rule[1]]) )
			{
				$group->parent[$rule[1]][] = new GroupElement();

				$group->pos   = count( $group->parent[$rule[1]] ) - 1;
				$group->group = &$group->parent[$rule[1]][$group->pos];
			}
			else
			{
				$group->parent[$rule[1]] = [new GroupElement()];

				$group->pos   = 0;
				$group->group = &$group->parent[$rule[1]][0];
			}

			$group->group->start     = $this->pos;
			$group->group->startLine = $this->GetLineFromPosition( $this->pos );

			$group->name = $rule[1];
		}
		else
		{
			// Logger::Log( "  - exit from temporary storage" );

			// zapisz pobraną treść
			$this->groups[0]->group->end     = $this->pos;
			$this->groups[0]->group->endLine = $this->GetLineFromPosition( $this->pos );
			$this->groups[0]->group->content = $this->gcontent[0];
			$this->gpos = $this->lastgpos;
		}

		// // główna grupa (grupy w głównym obiekcie)
		// if( $rule[1] && $this->gpos < 0 )
		// {
		// 	$this->gpos++;

		// 	$group = &$this->groups[$this->gpos];
		// 	$this->gcontent[$this->gpos] = '';

		// 	if( isset($group->parent[$rule[1]]) )
		// 	{
		// 		$group->parent[$rule[1]][] = new GroupElement();

		// 		$group->pos   = count( $group->parent[$rule[1]] ) - 1;
		// 		$group->group = &$group->parent[$rule[1]][$group->pos];
		// 	}
		// 	else
		// 	{
		// 		$group->parent[$rule[1]] = [new GroupElement()];

		// 		$group->pos   = 0;
		// 		$group->group = &$group->parent[$rule[1]][0];
		// 	}

		// 	$group->group->start     = $this->pos;
		// 	$group->group->startLine = $this->GetLineFromPosition($this->pos);

		// 	$group->name = $rule[1];
		// }
		// // podgrupa
		// else if( $rule[1] )
		// {
		// }
		// // wyjście z grupy
		// else
		// {
		// 	// nie ma z czego wychodzić
		// 	if( $this->gpos == -1 )
		// 		return true;

		// 	// zapisz pobraną treść
		// 	$this->groups[$this->gpos]->group->end     = $this->pos;
		// 	$this->groups[$this->gpos]->group->endLine = $this->GetLineFromPosition($this->pos);
		// 	$this->groups[$this->gpos]->group->content = $this->gcontent[$this->gpos];
		// 	$this->gpos--;
		// }

		return true;
	}

	/**
	 * Wchodzi lub wychodzi z grupy do której będą zapisywane dane.
	 *
	 * DESCRIPTION:
	 *     Grupy można zagnieżdżać, wchodząc w grupie do innej grupy.
	 *     Wyjście z grupy można osiągnąć podając wartość FALSE zamiast nazwy grupy.
	 *     Do grupy zawsze przypisywana jest linia oraz pozycja jej rozpoczęcia i zakończenia.
	 *     Każde wejście do grupy musi posiadać w kodzie wyjście z niej, gdyż zapis treści do
	 *     grupy odbywa się dopiero na samym końcu.
	 *
	 * CODE:
	 *     // ustawia grupę GROUP_DATA jako aktywną
	 *     $this->Group( ['', 'GROUP_DATA'] );
	 *     ...
	 *     // wychodzi z grupy
	 *     $this->Group( ['', false] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Nazwa grupy do której program ma wejść lub FALSE gdy ma wyjść.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function Group( array &$rule ): bool
	{
		// if( $rule[1] )
			// Logger::Log( "  - enter to: {$rule[1]}" );
		// else
			// Logger::Log( "  - exit from: {$this->groups[$this->gpos]->name}" );

		// główna grupa (grupy w głównym obiekcie)
		if( $rule[1] && $this->gpos < 1 )
		{
			$group = &$this->groups[++$this->gpos];
			$this->gcontent[$this->gpos] = '';

			if( isset($group->parent[$rule[1]]) )
			{
				$group->parent[$rule[1]][] = new GroupElement();

				$group->pos   = count( $group->parent[$rule[1]] ) - 1;
				$group->group = &$group->parent[$rule[1]][$group->pos];
			}
			else
			{
				$group->parent[$rule[1]] = [new GroupElement()];

				$group->pos   = 0;
				$group->group = &$group->parent[$rule[1]][0];
			}

			$group->group->start     = $this->pos;
			$group->group->startLine = $this->GetLineFromPosition( $this->pos );

			$group->name = $rule[1];
		}
		// podgrupa
		else if( $rule[1] )
		{

		}
		// wyjście z grupy
		else
		{
			// nie ma z czego wychodzić
			if( $this->gpos < 1 )
				return true;

			// zapisz pobraną treść
			$this->groups[$this->gpos]->group->end     = $this->pos;
			$this->groups[$this->gpos]->group->endLine = $this->GetLineFromPosition( $this->pos );
			$this->groups[$this->gpos]->group->content = $this->gcontent[$this->gpos];
			$this->gpos--;
		}

		return true;
	}

	/**
	 * Usypia działanie skryptu na podaną ilość sekund.
	 *
	 * CODE:
	 *     $this->Sleep( ['', 1] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Ilość sekund uśpienia skryptu.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function Sleep( array &$rule ): bool
	{
		sleep( $rule[1] );
		return true;
	}

	/**
	 * Zapisuje znak podany w parametrze do grupy lub aktualny znak ze strumienia.
	 * 
	 * DESCRIPTION:
	 *     W zależności od argumentu zapisuje podany znak lub znak ze strumienia.
	 *     W przypadku gdy zapisywany jest znak ze strumienia, pozycja kursora jest zwiększana.
	 *
	 * CODE:
	 *     $this->Write( ['', 'w'] );
	 *     $this->Write( ['', false] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Znak zapisywany do grupy lub FALSE w przypadku zapisu ze strumienia.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function Write( array &$rule ): bool
	{
		$this->gcontent[$this->gpos] .= $rule[1]
			? $rule[1]
			: $this->data[$this->pos++];
		return true;
	}

	/**
	 * Wykonuje regułę przypisaną do znaku gdy ten się zgadza.
	 * 
	 * DESCRIPTION:
	 *     Sprawdza aktualny znak w strumieniu danych.
	 *     Gdy zgadza się z jednym z tych podanych w tablicy, wykonywana jest przypisana
	 *     do niego reguła.
	 *     Zamiast nazwy reguły można podać wartość FALSE, wtedy funkcja tylko przesunie kursor.
	 *     W zależności od podanego ostatniego argumentu, funkcja może zwrócić TRUE lub FALSE.
	 *     Gdy nie pasuje żaden znak, funkcja zwraca TRUE.
	 *
	 * CODE:
	 *     $this->Char( ['', [
	 *             '>': 'RULE_ON_CHEVRON',
	 *             '@': 'RULE_ON_AT'
	 *         ],
	 *         'RULE_WHEN_NOT_LISTED',
	 *         true
	 *     );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Lista znaków do sprawdzenia wraz z regułą do wykonania.
	 *           - 2 > Reguła wykonywana w przypadku gdy żaden ze znaków nie spełnia wymagań.
	 *           - 3 > Co funkcja po wykonaniu reguły ma zwrócić?
	 *
	 * RETURNS:
	 *     Wartość TRUE lub FALSE w zależności od dopasowania znaku i ostatniego argumentu.
	 */
	protected function Char( &$rule ): bool
	{
		if( $this->length <= $this->pos )
			return false;

		// Logger::Log('  - [' . $this->ReadableCharArray($rule[1]) . '] is: "' .
			// $this->ReadableChar($this->data[$this->pos]) . '"' );

		// jeżeli znak istnieje w tablicy
		if( isset($rule[1][$this->data[$this->pos]]) )
		{
			if( $rule[1][$this->data[$this->pos]] )
			{
				if( $rule[1][$this->data[$this->pos]] != ' ' )
					$this->Run( $rule[1][$this->data[$this->pos++]] );
				return $rule[3];
			}
			else
				$this->pos++;
		}
		// w przeciwnym razie sprawdź ustawioną regułę i ją wywołaj
		else if( $rule[2] )
		{
			if( $rule[2] != ' ' )
				$this->Run( $rule[2] );
			return $rule[3];
		}

		return true;
	}

	/**
	 * Sprawdza znak i uruchamia odpowiednią regułę.
	 * 
	 * DESCRIPTION:
	 *     Sprawdza aktualny znak w strumieniu danych.
	 *     Gdy zgadza się z podanym w argumencie, wykonywana jest przypisana reguła.
	 *     W przeciwnym wypadku wywoływana jest druga reguła.
	 *     Do każdej z reguł można przypisać wartość FALSE, spowoduje to zwrócenie przez funkcję
	 *     wartości przekazanej jako ostatni argument.
	 *     W zależności od podanego ostatniego argumentu, funkcja może zwrócić TRUE lub FALSE.
	 *     Gdy nie pasuje żaden znak, funkcja zwraca TRUE.
	 *
	 * CODE:
	 *     $this->Next( ['', '@', 'RULE_ON_AT', 'RULE_ON_ELSE', true );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Znak do sprawdzenia.
	 *           - 2 > Reguła wykonywana w przypadku gdy znak jest równy temu ze strumienia.
	 *           - 3 > Reguła wykonywana w przypadku gdy znak nie jest równy temu ze strumienia.
	 *           - 4 > Co funkcja po wykonaniu reguły ma zwrócić?
	 *
	 * RETURNS:
	 *     Wartość TRUE lub FALSE w zależności od dopasowania znaku i ostatniego argumentu.
	 */
	protected function Next( &$rule ): bool
	{
		// Logger::Log('  - expecting: "' . $this->ReadableChar($rule[1]) . '" is: "' .
			// $this->ReadableChar($this->data[$this->pos]) . '"' );

		if( $rule[1] == $this->data[$this->pos] )
		{
			if( $rule[2] )
			{
				$this->pos++;
				if( $rule[2] != ' ' )
					$this->Run( $rule[2] );
				return $rule[4];
			}
		}
		else if( $rule[3] )
		{
			if( $rule[3] != ' ' )
				$this->Run( $rule[3] );
			return $rule[4];
		}
		return true;
	}

	/**
	 * Uruchamia zdefiniowaną wcześniej regułę.
	 *
	 * CODE:
	 *     $this->Run( 'ENTRY' );
	 *
	 * PARAMETERS:
	 *     $rule Nazwa reguły do wykonania.
	 */
	public function Run( string $rule = 'ENTRY' ): void
	{
		// Logger::Log( "> {$rule}" );

		if( $this->length <= $this->pos )
			return;
		// Logger::IndentInc();

		foreach( $this->rules[$rule] as $val )
		{
			// Logger::Log( "+ {$val[0]}" );
			if( !$this->{$val[0]}($val) )
				break;
		}

		// Logger::IndentDec();
	}

	protected function String( &$rule ): bool
	{
		if( !preg_match($rule[1], $this->data, $matches, 0, $this->pos) )
			return true;

		$this->pos += strlen( $matches[0] );

		if( isset($rule[2][$matches[0]]) )
		{
			if( $rule[2][$matches[0]] != ' ' )
				$this->Run( $rule[2][$matches[0]] );
			return $rule[4];
		}
		else if( $rule[3] )
		{
			if( $rule[3] != ' ' )
				$this->Run( $rule[3] );
			return $rule[4];
		}

		return true;
	}

	protected function Capture( &$rule ): bool
	{
		if( !preg_match($rule[1], $this->data, $matches, 0, $this->pos) )
		{
			if( $rule[2] !== false )
				$this->Run( $rule[2] );
			return true;
		}

		$this->pos += strlen( $matches[0] );
		$this->results[] = $matches[0];

		return true;
	}

	/**
	 * Zamienia znaki kończące linie z CR+LF i CR na LF.
	 *
	 * DESCRIPTION:
	 *     Unifikuje wszystkie znaki końca linii na te znane z linuksa.
	 *     Podczas zamiany funkcja oblicza również przedziały znaków w których znajduje się dana linia.
	 *     Są one potrzebne podczas wchodzenia i wychodzenia z grupy.
	 *     Funkcję tą warto wywołać zaraz przed uruchomieniem samego parsera.
	 */
	public function UnificateLineEndings(): void
	{
		$this->linefeeds = [];
		$this->linefeeds[] = -1;

		for( $x = 0; $x < $this->length; ++$x )
			if( $this->data[$x] == "\r" )
			{
				if( $this->data[$x + 1] == "\n" )
					$this->data[$x++] = ' ';
				else
					$this->data[$x] = "\n";
				$this->linefeeds[] = $x;
			}
			else if( $this->data[$x] == "\n" )
				$this->linefeeds[] = $x;
	}

	/**
	 * Pobiera numer linii znajdujący się na podanej pozycji.
	 *
	 * DESCRIPTION:
	 *     Funkcja korzysta z optymalizacji podczas pobierania numeru linii.
	 *     Zakłada ona, że każda kolejna pozycja będzie większa lub równa obecnej.
	 *     Sprawdza się to doskonale podczas przetwarzania danych kolejno po sobie.
	 *     Funkcja wywoływana jest głównie podczas wchodzenia i wychodzenia z grup.
	 *
	 * PARAMETERS:
	 *     $pos Pozycja względem której liczony ma być numer linii.
	 *
	 * RETURNS:
	 *     Numer linii znajdujący się na podanej pozycji.
	 */
	public function GetLineFromPosition( int $pos ): int
	{
		if( $pos < $this->lfcpos )
		{
			for( ; $this->lfpos > 0; --$this->lfpos )
				if( $pos > $this->linefeeds[$this->lfpos] )
					return $this->lfpos;
		}
		else
		{
			for( $y = count($this->linefeeds); $this->lfpos < $y; ++$this->lfpos )
				if( $pos <= $this->linefeeds[$this->lfpos] )
					return $this->lfpos;
		}
		return 0;
	}

	public function GetRulesFromFile( string $file ): void
	{
		if( !file_exists($file) )
			return;

		$this->rules = [];
		$rules = json_decode( file_get_contents($file), true );

		if( isset($rules['STEPS']) )
			foreach( $rules['STEPS'] as $name => $step )
			{
				$rule = [];
				foreach( $step as $key => $value )
				{
					if( !isset($this->mappings[$value[0]]) )
					{
						// Logger::Log( "ERROR: Function ${$value[0]} not exist!" );
						continue;
					}

					$map    = $this->mappings[$value[0]];
					$single = [$map[1]];

					for( $x = 0, $y = 1; $x < $map[2]; ++$x )
						if( $map[3][$x] == -1 )
							$single[] = $value[$y++];
						else
							$single[] = $map[0][$map[3][$x]];

					$rule[] = $single;
				}
				if( count($rule) > 0 )
					$this->rules[$name] = $rule;
			}
	}

	/**
	 * Sprawdza czy znak jest znakiem specjalnym i zamienia go na zwykły tekst.
	 *
	 * PARAMETERS:
	 *     $chr Znak do sprawdzenia i zamiany.
	 *
	 * RETURNS:
	 *     Podmieniony tekst dla znaku lub oryginalny znak.
	 */
	private function ReadableChar( string $chr ): string
	{
		static $rplcs = [
			"\t" => '\t',
			"\n" => '\n',
			"\r" => '\r',
			'"'  => '\\"'
		];

		return isset( $rplcs[$chr] )
			? $rplcs[$chr]
			: $chr;
	}

	/**
	 * Zamienia tablicę znaków na tekst, sprawdzając i zamieniając przy okazji znaki specjalne.
	 *
	 * PARAMETERS:
	 *     $chr Tablica do konwersji i zamiany.
	 *
	 * RETURNS:
	 *     Tekst reprezentujący tablicę w postaci ciągu znaków.
	 */
	private function ReadableCharArray( array $chr ): string
	{
		$retv = '';

		foreach( $chr as $key => $val )
			$retv .= $retv == ''
				? '"' . $this->ReadableChar($key) . '"'
				: ', "' . $this->ReadableChar($key) . '"';

		return $retv;
	}
}


$berseker = new Berseker( './test/moss/tst/array_test.c' );
// $berseker = new Berseker( './test/main.c' );

$berseker->GetRulesFromFile( 'gramma/c.json' );
$berseker->UnificateLineEndings();
$berseker->Run( 'ENTRY' );

// echo $berseker->data;
// var_dump( $berseker->results );
file_put_contents('asdf.txt', $berseker->data);

file_put_contents('asdfg.txt', print_r($berseker->results, true));
file_put_contents('asdfgh.txt', print_r($berseker->storage, true));

// $parser->ExtractScope( $content );
// $parser->ParseElements();
