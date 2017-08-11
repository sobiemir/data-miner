<?php
require_once( "./logger.php" );

class GroupElement
{
	public $content;
	public $start;
	public $startLine;
	public $end;
	public $endLine;
	public $subgroups;

	public function __construct()
	{
		$this->content   = "";
		$this->start     = 0;
		$this->end       = 0;
		$this->subgroups = [];
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
	private $file = '';
	public $data = '';
	private $length = 0;
	private $pos = 0;

	private $rules = [];
	public $results = [];
	private $groups = [];
	private $gpos = -1;
	private $gcontent = [];
	private $linefeeds = [];
	private $lfpos = 0;
	private $lfcpos = 0;

	public function __construct( string $file )
	{
		$this->data = file_get_contents( $file );

		if( $this->data === false )
			exit;

		$this->file   = $file;
		$this->length = strlen( $this->data );
		$this->pos    = 0;
		$this->gpos   = -1;

		// wskaźnik na główną strukturę
		$this->groups[] = new GroupData( '', $this->results, 0 );
		$this->groups[0]->parent = &$this->results;

		$this->rules = [
			'ENTRY' => [
				['loop', 'ENTRY_LOOP'],
				['rewind', 0]
			],
			'ENTRY_LOOP' => [
				['uaseek',
					[
						'/' => false,
						'"' => false,
						"'" => false
					],
					false
				],
				['echar', [
					'"' => 'SKIP_CONTENT_DQUOTE',
					"'" => 'SKIP_CONTENT_SQUOTE',
					'/' => 'COMMENT'
				]]
			],
			'SKIP_CONTENT_DQUOTE' => [
				['uereplace', '"', '\\', ' ', true]
			],
			'SKIP_CONTENT_SQUOTE' => [
				['uereplace', "'", '\\', ' ', true]
			],
			'COMMENT' => [
				['char',
					[
						'/' => 'COMMENT_LINE_START',
						'*' => 'COMMENT_BLOCK_START'
					],
					false
				]
			],
			'COMMENT_BLOCK_START' => [
				['group', 'COMMENT_BLOCK'],
				['seek', -2, false],
				['replace', ' ', false],
				['replace', ' ', false],
				['execute', 'COMMENT_BLOCK'],
				['group', false]
			],
			'COMMENT_BLOCK' => [
				['uareplace',
					[
						"\r" => false,
						"\n" => false,
						'*'  => false
					],
					' ',
					true
				],
				['next',
					'*',
					'COMMENT_BLOCK_END_PREPARE',
					false
				],
				['seek', 1, true],
				['execute', 'COMMENT_BLOCK']
			],
			'COMMENT_BLOCK_END_PREPARE' => [
				['next',
					'/',
					'COMMENT_BLOCK_END',
					false
				],
				['seek', -1, false],
				['replace', ' ', true],
				['execute', 'COMMENT_BLOCK']
			],
			'COMMENT_BLOCK_END' => [
				['seek', -2, false],
				['replace', ' ', false],
				['replace', ' ', false]
			],
			'COMMENT_LINE_START' => [
				['group', 'COMMENT_LINE'],
				['seek', -1, false],
				['replace', ' ', false],
				['execute', 'COMMENT_LINE'],
				['group', false]
			],
			'COMMENT_LINE_CONTINUE' => [
				['seek', -1, false],
				['write', false],
				['execute', 'COMMENT_LINE']
			],
			'COMMENT_LINE' => [
				['seek', -2, false],
				['replace', ' ', false],
				['seek', 1, false],
				['uareplace',
					[
						'\\' => false,
						"\r" => false,
						"\n" => false 
					],
					' ',
					true
				],
				// dodatkowa grupa dla nowej linii
				['next', '\\', false, true ],
				['seek', 1, false],
				['char',
					[
						"\r" => 'COMMENT_LINE_CONTINUE',
						"\n" => 'COMMENT_LINE_CONTINUE'
					],
					false
				],
				['write', '\\'],
				['execute', 'COMMENT_LINE']
			]


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
		];
	}

	/**
	 * Przewija strumień do wybranego znaku.
	 *
	 * DESCRIPTION:
	 *     Funkcja zmienia pozycję kursora wskazującego na aktualny znak w strumieniu.
	 *     Pozycję należy podać w drugim parametrze w tablicy.
	 *     Podanie wartości ujemnej spowoduje przesunięcie kursora o podaną ilość znaków licząc od końca.
	 *
	 * CODE:
	 *     // przewija na sam początek strumienia
	 *     ['rewind', 0]
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function rewind( array &$rule ): bool
	{
		$this->pos = $rule[1] < 0
			? $this->pos + $rule[1]
			: $rule[1];

		Logger::Log( "  - rewinding to: {$this->pos}" );
		return true;
	}

	/**
	 * Tworzy pętlę powtarzającą podaną regułę.
	 *
	 * DESCRIPTION:
	 *     Funkcja tworzy pętlę w której wywołuje wszystkie parametry podanej reguły.
	 *     Nazwę reguły należy podać w drugim parametrze.
	 *
	 * CODE:
	 *     // tworzy pętle dla reguły "loop_rule"
	 *     ['loop', 'loop_rule']
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function loop( array &$rule ): bool
	{
		$isrun = true;
		$lrule = &$this->rules[$rule[1]];

		while( $isrun )
		{
			Logger::Log( "> {$rule[1]}" );

			if( $this->length <= $this->pos )
				break;
			Logger::IndentInc();

			foreach( $lrule as $val )
			{
				Logger::Log( "+ {$val[0]}" );
				if( !$this->{$val[0]}($val) )
				{
					$isrun = false;
					break;
				}
			}

			Logger::IndentDec();
		}
		return true;
	}

	/**
	 * Wywołuje regułę podaną w parametrze.
	 *
	 * DESCRIPTION:
	 *     Reguła podana w drugim parametrze zostaje natychmiast uruchomiona.
	 *     Funkcja nie kończy działania reguły z której została wywołana po zakończeniu wywołanej reguły.
	 *
	 * CODE:
	 *     // wywołuje regułę "exec_rule"
	 *     ['execute', 'exec_rule']
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function execute( &$rule ): bool
	{
		$this->run( $rule[1] );
		return true;
	}

	/**
	 * Przesuwa kursor na dane o podaną ilość pól.
	 *
	 * DESCRIPTION:
	 *     Kursor można przesuwać zarówno do przodu jak i do tyłu.
	 *     Wszystko zależy od tego czy w drugim argumencie podana zostanie wartość dodatnia czy ujemna.
	 *     Dodatkowo przesunięte dane można pobrać, podając wartość true do drugiego argumentu.
	 *     Dane zawsze pobierane są od początku - nie ma więc sytuacji, że są odwrócone gdy wartość
	 *     przesunięcia podana w pierwszym argumencie jest ujemna.
	 *
	 * CODE:
	 *     // przesuwa kursor o dwa pola do tyłu bez pobierania danych
	 *     ['seek', -2, false]
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function seek( array &$rule ): bool
	{
		Logger::Log( "  - [{$rule[1]} : \"" . $this->readableChar($this->data[$this->pos + $rule[1]]) .
			"\"] from [{$this->pos} : \"" . $this->readableChar($this->data[$this->pos]) . "\"]" );

		if( $rule[2] )
		{
			Logger::Log( "  - save data to current group" );
			if( $rule[1] > 0 )
				$this->gcontent[$this->gpos] .= substr( $this->data, $this->pos, $rule[1] );
			else
				$this->gcontent[$this->gpos] .= substr( $this->data, $this->pos - $rule[1], -$rule[1] );
		}

		$this->pos += $rule[1];
		return true;
	}

	protected function rseek( array &$rule ): bool
	{
		Logger::Log( "  - to [{$rule[1]} : \"" . $this->readableChar($this->data[$this->pos + $rule[1]]) .
			"\"] from [{$this->pos} : \"" . $this->readableChar($this->data[$this->pos]) . "\"]" );

		// pobierz znaki
		if( $rule[3] )
		{
			Logger::Log( "  - save data to current group" );
			if( $rule[1] > 0 )
				$this->gcontent[$this->gpos] .= substr( $this->data, $this->pos, $rule[1] );
			else
				$this->gcontent[$this->gpos] .= substr( $this->data, $this->pos - $rule[1], -$rule[1] );
		}

		// przesuń kursor i zamieniaj poszczególne znaki
		if( $rule[2] )
		{
			Logger::Log( "  - replace all values to: {$rule[2]}" );
			if( $rule[1] < 0 )
				for( $x = $this->pos - $rule[1]; $this->pos > $x; --$this->pos )
					$this->data[$this->pos] = $rule[2];
			else
				for( $x = $this->pos + $rule[1]; $this->pos < $x; ++$this->pos )
					$this->data[$this->pos] = $rule[2];
		}
		else
			$this->pos += $rule[1];

		return true;
	}

	/**
	 * Zamienia aktualny znak na ten podany w parametrze reguły.
	 *
	 * DESCRIPTION:
	 *     Zamiana znaku powoduje przesunięcie kursora o jeden znak w prawo.
	 *     Zamieniany znak podawany jest w drugim parametrze reguły.
	 *
	 * CODE:
	 *     // zamienia aktualny znak na znak "$"
	 *     ['replace', '$']
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function replace( array &$rule ): bool
	{
		Logger::Log( '  - "' . $this->readableChar($this->data[$this->pos]) . '" to "'
			. $this->readableChar($rule[1]) . '"' );

		// pobierz znaki
		if( $rule[2] )
		{
			Logger::Log( "  - save data to current group" );
			$this->gcontent[$this->gpos] .= $this->data[$this->pos];
		}

		$this->data[$this->pos++] = $rule[1];
		return true;
	}

	/**
	 * Zamienia wszystkie znaki w strumieniu aż do napotkania podanego znaku.
	 *
	 * DESCRIPTION:
	 *     Wszystkie znaki zostają zamienione na podany w regule znak.
	 *     Pierwszy argument to znak do którego wszystkie pozostałe będą zamieniane.
	 *     Drugi argument to wartość na którą będą zamieniane znaki.
	 *     Skrót "ureplace" od "until replace".
	 *     Trzeci parametr odpowiada za pobieranie znaku do aktualnej grupy przed jego zamianą.
	 *
	 * CODE:
	 *     // zamienia znaki na znak "$" aż do wykrycia "/" bez pobierania
	 *     ['ureplace', '/', $', false]
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function ureplace( array &$rule ): bool
	{
		Logger::Log( '  - "' . $this->readableChar($rule[2]) . '" untill "' .
			$this->readableChar($rule[1]) . '"' );

		// pobieraj znaki i zamieniaj je
		if( $rule[3] )
		{
			Logger::Log( '  - inserting into group' );

			while( $this->data[$this->pos] != $rule[1] )
			{
				$this->gcontent[$this->gpos] .= $this->data[$this->pos];
				$this->data[$this->pos++] = $rule[2];
			}
		}
		// pomijaj znaki i zamieniaj je
		else
			while( $this->data[$this->pos] != $rule[1] )
				$this->data[$this->pos++] = $rule[2];

		$this->pos++;
		return true;
	}

	/**
	 * Zamienia wszystkie znaki w strumieniu aż do napotkania jednego z podanych znaków.
	 *
	 * DESCRIPTION:
	 *     Wszystkie znaki zostają zamienione na podany w regule znak.
	 *     Pierwszy argument to lista znaków do których wszystkie pozostałe będą zamieniane.
	 *     Drugi argument to wartość na którą będą zamieniane znaki.
	 *     Skrót "uareplace" od "until array replace".
	 *     Trzeci parametr odpowiada za pobieranie znaku do aktualnej grupy przed jego zamianą.
	 *     Tablica zawierająca znaki stopu przyjmuje dwie wartości - true lub false w zależności od tego,
	 *     czy znak ma zostać pobrany po zatrzymaniu pętli czy też nie.
	 *
	 * CODE:
	 *     // zamienia znaki na znak "$" aż do wykrycia jednego z podanych
	 *     // "/", "]" lub ";" oraz pobiera je do aktualnej grupy przed zamianą
	 *     ['uareplace', [
	 *             '/' => false,
	 *             ']' => false,
	 *             ';' => false
	 *         ],
	 *         '$', 
	 *         true
	 *     ]
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function uareplace( array &$rule ): bool
	{
		Logger::Log( '  - "' . $this->readableChar($rule[2]) . '" untill one of [' .
			$this->readableCharArray($rule[1]) . ']' );

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
	 * Pomija białe znaki występujące w tekście.
	 *
	 * DESCRIPTION:
	 *     Do białych znaków zaliczają się znaki nowej linii, spacji i tabulacji.
	 *     Funkcja pomija te znaki do napotkania znaku innego niż te wymienione powyżej.
	 *     Sprawdzany jest również aktualny znak.
	 *     Do reguły podawany jest drugi parametr, odpowiedzialny za znak ucieczki.
	 *     Gdy znak podany w drugim parametrze bezpośrednio przed znakiem nowej linii jest
	 *     traktowany jako biały znak.
	 *     W przypadku braku znaku ucieczki, należy podać wartość FALSE.
	 *
	 * CODE:
	 *     // pomija białe znaki, traktując znak \ zaraz przed nową linią jako znak ucieczki
	 *     ['whitespace', '\\']
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function whitespace( array &$rule ): bool
	{
		// lista białych znaków
		static $white = [
			"\r" => true,
			"\n" => true,
			' '  => true,
			"\t" => true
		];

		// nowa linia ze znakiem uciekającym
		if( $rule[1] )
			do
			{
				if( isset($white[$this->data[$this->pos]]) )
					$this->pos++;
				else if( $this->data[$this->pos] == $rule[1] &&
					($this->data[$this->pos + 1] == "\r" || $this->data[$this->pos + 1] == "\n") )
					$this->pos += 2;
				else
					return true;
			}
			while( $this->pos < $this->length );
		// nowa linia bez znaku uciekającego
		else
			do
				if( isset($white[$this->data[$this->pos]]) )
					$this->pos++;
				else
					return true;
			while( $this->pos < $this->length );

		return false;
	}

	/**
	 * Przewija kursor do napotkania podanego znaku.
	 *
	 * DESCRIPTION:
	 *     Pomijane dane można pobrać do aktualnej grupy, podając jako trzeci parametr wartość TRUE.
	 *     Znak stopu podawany jest jako drugi parametr.
	 *     Skrót "useek" od "until seek".
	 *
	 * CODE:
	 *     // pomija znaki do napotkania znaku "/"
	 *     ['useek', '/', false]
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function useek( array &$rule ): bool
	{
		Logger::Log('  - seek until "' . $this->readableChar($rule[1]) . '"' );

		// przewijaj i pobieraj znaki
		if( $rule[2] )
			while( $this->length > $this->pos && $this->data[$this->pos] != $rule[1] )
				$this->pos++;
		// tylko przewijaj
		else
			while( $this->length > $this->pos && $this->data[$this->pos] != $rule[1] )
				$this->pos++;

		if( $this->length <= $this->pos )
			return false;

		return true;
	}

	/**
	 * Przewija kursor do napotkania podanego znaku.
	 *
	 * DESCRIPTION:
	 *     Pomijane dane można pobrać do aktualnej grupy, podając jako trzeci parametr wartość TRUE.
	 *     Znak stopu podawany jest jako drugi parametr.
	 *     Skrót "uaseek" od "until array seek".
	 *     Podanie wartości po indeksie oznacza pobieranie wykrytego znaku lub nie.
	 *
	 * CODE:
	 *     // przewija i pobiera znaki aż do napotkania jednego ze znaków "/", "*" lub "$"
	 *     ['uaseek', [
	 *             '/' => false,
	 *             '*' => false,
	 *             '$' => false
	 *         ],
	 *         true
	 *     ]
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	protected function uaseek( array &$rule ): bool
	{
		Logger::Log('  - seek until one of [' . $this->readableCharArray($rule[1]) . ']' );

		// przewijaj i pobieraj znaki
		if( $rule[2] )
			while( $this->length > $this->pos && !isset($rule[1][$this->data[$this->pos]]) )
				$this->pos++;
		// tylko przewijaj
		else
			while( $this->length > $this->pos && !isset($rule[1][$this->data[$this->pos]]) )
				$this->pos++;

		// pobierz ostatni znak gdy tak zostało podane w regule
		if( $this->length > $this->pos && $rule[1][$this->data[$this->pos]] )
			$this->pos++;

		return true;
	}

	protected function group( array &$rule ): bool
	{
		if( $rule[1] )
			Logger::Log( "  - name: {$rule[1]}" );
		else
			Logger::Log( "  - exit from: {$this->groups[$this->gpos]->name}" );

		if( $rule[1] && $this->gpos < 0 )
		{
			$this->gpos++;

			$group = &$this->groups[$this->gpos];
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
			$group->group->startLine = $this->getLineFromPosition($this->pos);

			$group->name = $rule[1];
		}
		else if( $rule[1] )
		{

		}
		else
		{
			// zapisz pobraną treść
			$this->groups[$this->gpos]->group->end     = $this->pos;
			$this->groups[$this->gpos]->group->endLine = $this->getLineFromPosition($this->pos);
			$this->groups[$this->gpos]->group->content = $this->gcontent[$this->gpos];
			$this->gpos--;
		}

		return true;
	}

	/**
	 * Szuka znaku zamykającego blok.
	 * Funkcja uwzględnia w pomijaniu bloki wewnętrzne.
	 *
	 * Przykład:
	 * dskip( ['dskip', '{', '}'] );
	 *
	 * Takie wywołanie funkcji bezpośrednio po znaku { pominie wszystkie pobrane znaki aż do
	 * napotkania najbardziej zewnętrznego znaku }.
	 * 
	 * @param  array &$rule Reguła względem której pobierane są znaki bloku.
	 * @return bool         Zawsze zwraca wartość TRUE.
	 */
	protected function dskip( array &$rule ): bool
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
			$pos++;
		}
		return true;
	}

	protected function sleep( array &$rule ): bool
	{
		sleep( $rule[1] );
		return true;
	}

	protected function write( array &$rule ): bool
	{
		$this->gcontent[$this->gpos] .= $rule[1]
			? $rule[1]
			: $this->data[$this->pos++];
		return true;
	}

	protected function char( &$rule ): bool
	{
		if( $this->length <= $this->pos )
			return false;

		Logger::Log('  - [' . $this->readableCharArray($rule[1]) . '] is: "' .
			$this->readableChar($this->data[$this->pos]) . '"' );

		// jeżeli znak istnieje w tablicy
		if( isset($rule[1][$this->data[$this->pos]]) )
		{
			if( $rule[1][$this->data[$this->pos]] !== false )
				$this->run( $rule[1][$this->data[$this->pos++]] );
			else
				$this->pos++;

			return false;
		}
		// w przeciwnym razie sprawdź ustawioną regułę i ją wywołaj
		else if( $rule[2] )
		{
			$this->run( $rule[2] );
			return false;
		}

		return true;
	}

	protected function echar( &$rule ): bool
	{
		if( $this->length <= $this->pos )
			return false;

		Logger::Log('  - [' . $this->readableCharArray($rule[1]) . '] is: "' .
			$this->readableChar($this->data[$this->pos]) . '"' );

		if( isset($rule[1][$this->data[$this->pos]]) )
		{
			if( $rule[1][$this->data[$this->pos]] !== false )
				$this->run( $rule[1][$this->data[$this->pos++]] );
			else
				$this->pos++;
		}
		else if( $rule[2] )
			$this->run( $rule[2] );

		return true;
	}

	protected function next( &$rule ): bool
	{
		Logger::Log('  - expecting: "' . $this->readableChar($rule[1]) . '" is: "' .
			$this->readableChar($this->data[$this->pos]) . '"' );

		if( $rule[1] == $this->data[$this->pos] )
		{
			if( $rule[2] )
			{
				$this->pos++;
				if( is_string($rule[2]) )
					$this->run( $rule[2] );
				return false;
			}
			return true;
		}
		else if ( $rule[3] )
		{
			if( is_string($rule[3]) )
				$this->run( $rule[3] );
			return false;
		}
		return true;
	}

	protected function string( &$rule ): bool
	{
		if( !preg_match($rule[1], $this->data, $matches, 0, $this->pos) )
			return true;

		$this->pos += strlen( $matches[0] );

		if( isset($rule[2][$matches[0]]) )
		{
			$this->run( $rule[2][$matches[0]] );
			return false;
		}
		else if( $rule[3] )
		{
			$this->run( $rule[3] );
			return false;
		}

		return true;
	}

	protected function ueseek( &$rule ): bool
	{
		$escape = false;

		while( $this->length > $this->pos )
		{
			// szukaj znaku ucieczki
			if( $this->data[$this->pos] == $rule[2] )
				$escape = !$escape;

			// koniec
			else if( $this->data[$this->pos] == $rule[1] && !$escape )
			{
				$this->pos++;
				break;
			}
			
			// i po znaku ucieczki
			else
				$escape = false;

			$this->pos++;
		}
		return true;
	}

	protected function uereplace( &$rule ): bool
	{
		$escape = false;

		while( $this->length > $this->pos )
		{
			// szukaj znaku ucieczki
			if( $this->data[$this->pos] == $rule[2] )
				$escape = !$escape;
			// koniec
			else if( $this->data[$this->pos] == $rule[1] && !$escape )
			{
				$this->pos++;
				break;
			}
			else
				$escape = false;

			$this->data[$this->pos] = $rule[3];

			$this->pos++;
		}
		return true;
	}

	protected function capture( &$rule ): bool
	{
		if( !preg_match($rule[1], $this->data, $matches, 0, $this->pos) )
		{
			if( $rule[2] !== false )
				$this->run( $rule[2] );
			return true;
		}

		$this->pos += strlen( $matches[0] );
		$this->results[] = $matches[0];

		return true;
	}

	public function run( string $point = 'ENTRY' ): void
	{
		Logger::Log( "> {$point}" );

		if( $this->length <= $this->pos )
			return;
		Logger::IndentInc();

		foreach( $this->rules[$point] as $val )
		{
			Logger::Log( "+ {$val[0]}" );
			if( !$this->{$val[0]}($val) )
				break;
		}

		Logger::IndentDec();
	}

	// ZAMIENIA CR+LF i CR na samo LF
	public function unificateLineEndings(): void
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

	public function readableChar( $chr )
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

	public function readableCharArray( $chr )
	{
		$retv = '';

		foreach( $chr as $key => $val )
			$retv .= $retv == ''
				? '"' . $this->readableChar($key) . '"'
				: ', "' . $this->readableChar($key) . '"';

		return $retv;
	}

	public function getLineFromPosition( $pos )
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
}

// $parser = new Parser( './test/moss/tst/array_test.c' );
// $parser = new Parser( './test/main.c' );
// $parser->Run();

$berseker = new Berseker( './test/moss/tst/array_test.c' );
// $berseker = new Berseker( './test/main.c' );

$berseker->unificateLineEndings();
$berseker->run('ENTRY');

// echo $berseker->data;
var_dump( $berseker->results );
// $parser->ExtractScope( $content );
// $parser->ParseElements();
