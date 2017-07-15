<?php

abstract class ZSElementType
{
	const Include = 1;
	const Define  = 2;
}

class Element
{
	public $type;
	public $content;
}

$first = [
	'#',
	'/'
];

class RuleInfo
{
	public $first;
}

class Berseker
{
	private $file;
	private $data;
	private $length;
	private $pos;

	private $rules;
	public $results;

	public function __construct( string $file )
	{
		$this->data = file_get_contents( $file );

		if( $this->data === false )
			exit;

		$this->file   = $file;
		$this->length = strlen( $this->data );
		$this->pos    = 0;

		$this->startRule = 'entry_2';
		$this->rules = [
			'entry_2' => [
				2,
				['next',
					'/',
					'comment_2',
					'entry_2'
				]
			],
			'comment_2' => [
				2,
				['char',
					[
						'/' => 'comment_line_2',
						'*' => 'comment_block_2'
					],
					'entry_2'
				]
			],
			'comment_line_2' => [
				5,
				['undo', 2],
				['uagreplace',
					[
						'\\' => false,
						"\r" => false,
						"\n" => false 
					],
					' '
				],
				['ibchar', '\\'],
				['char',
					[
						"\r" => 'comment_line_2',
						"\n" => 'comment_line_2'
					],
					false
				]
			],
			'comment_block_2' => [
				7,
				['undo', 2],
				['replace', ' '],
				['replace', ' '],
				['ugreplace',
					'*',
					' '
				],
				['next',
					'/',
					false,
					'comment_block_2'
				],
				['replace', ' ']
			],


			// reguła wejściowa
			'entry' => [
				5,
				// pomija białe znaki
				['whitespace', true],
				// porównuje znak i przechodzi do reguły podanej po znaku
				// ta wersja kontynuuje działanie reguły po wykonaniu makra
				['echar', 
					[
						'#' => 'macro',
						'/' => 'comment'
					],
					// reguła wykonywana w przeciwnym razie lub false
					'symbol'
				],
				// przechodzi do podanej reguły
				['goto', 'entry']
			],

			'macro' => [
				3,
				['whitespace', true],
				// porównuje pobrany ciąg znaków z podanymi i przechodzi do reguły podanej po ciągu
				['string', 
					// regex używany do pobrania ciągu z tekstu
					'/[a-z]+/A',
					[
						'include' => 'macro_include',
						'define' => 'macro_define'
					],
					// reguła wykonywana w przeciwnym razie lub false
					'skip_line'
				]
			],
			
			'macro_include' => [
				3,
				['whitespace', true],
				// to samo co echar z tą różnicą że po wykonaniu reguły obecna jest przerywana
				['char',
					[
						'<' => 'macro_include_file',
						'"' => 'macro_include_file'
					],
					'skip_line'
				]
			],

			'macro_include_file' => [
				3,
				// zapisuje do kolekcji pobrany ciąg
				['capture',
					'/[^">\r\n]+/A',
					// w przypadku gdy program nie znajdzie dopasowania, wykonana zostanie podana tutaj reguła
					// false nie wykonuje żadnej reguły
					false
				],
				['goto', 'skip_line']
			],

			'macro_define' => [
				4,
				['whitespace', true],
				['capture',
					'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
					false
				],
				// sprawdza czy następny znak jest równy podanemu i wykonuje jedną z podanych
				// reguł w zależności czy sprawdzenie się powiodło czy nie
				['next',
					'(',
					'macro_define_param',
					'skip_line'
				]
			],

			'macro_define_param' => [
				5,
				['whitespace', true],
				['capture',
					'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
					false
				],
				['whitespace', true],
				['next',
					',',
					'macro_define_param',
					'skip_line'
				]
			],

			// pomijanie linii
			'skip_line' => [
				4,
				// pomija dane aż do napotkania jednego z podanych znaków
				// tru pobiera znak, false pozostawia (uskip - until skip)
				['uskip', [
					'\\' => false,
					"\r" => false,
					"\n" => false
				]],
				// przerywa dalsze działanie gdy znak nie jest równy podanemu
				// gdy jest, pobiera go (ibchar - inverse break char)
				['ibchar', '\\'],
				// jeżeli kolejnym znakiem jest znak nowej linii, to goto skip_line
				['char',
					[
						"\r" => 'skip_line',
						"\n" => 'skip_line'
					],
					false
				]
			],

			// rozpoznanie typu komentarza
			'comment' => [
				2,
				['char',
					[
						'/' => 'comment_line',
						'*' => 'comment_block'
					],
					'skip_line'
				]
			],

			// komentarz liniowy
			'comment_line' => [
				4,
				// pobieraj dane do napotkania jednego ze znaków (until get)
				['uget', [
					'\\' => false,
					"\r" => false,
					"\n" => false
				]],
				['ibchar', '\\'],
				['char',
					[
						"\r" => 'comment_line',
						"\n" => 'comment_line'
					],
					false
				]
			],

			// komentarz blokowy
			'comment_block' => [
				3,
				['uget', [
					'*' => true
				]],
				['next',
					'/',
					false,
					'comment_block'
				]
			],

			'symbol' => [
				5,
				['whitespace', true],
				['capture',
					'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
					'symbol_skip'
				],
				['whitespace', true],
				['char',
					[
						'(' => 'symbol_params',
						';' => false,
						'=' => 'symbol_skip',
						'{' => 'symbol_inner_symbol'
					],
					'symbol'
				]
			],

			'symbol_inner_symbol' => [
				5,
				['whitespace', true],
				['capture',
					'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
					'symbol_skip'
				],
				['whitespace', true],
				['char',
					[
						'*' => 'symbol_inner_pointer',
						';' => 'symbol_inner_symbol'
					],
					'symbol_inner_symbol'
				]
			],

			'symbol_skip' => [
				3,
				['uskip', [
					';' => false,
					',' => false,
					'{' => false
				]],
				['char',
					[
						',' => false,
						';' => false,
						'{' => false
					],
					false
				]
			],

			'symbol_body_recognize' => [
				3,
				['whitespace', true],
				['next',
					'{',
					'symbol_body_skip',
					'symbol_skip'
				]
			],

			'symbol_body_skip' => [
				2,
				// deep skip
				// pomijanie znaków do napotkania najbardziej zewnętrznego znaku zamykającego obszar
				// porównuje poziomy i zamyka w odpowiednim momencie
				['dskip', '{', '}']
			],

			'symbol_params' => [
				5,
				['whitespace', true],
				['char',
					[
						'*' => 'symbol_pointer',
						',' => 'symbol_params',
						')' => 'symbol_body_recognize'
					],
					false
				],
				['capture',
					'/[a-zA-Z_$][a-zA-Z0-9_$]*/A',
					'symbol_skip'
				],
				['goto', 'symbol_params']
			],

			'symbol_pointer' => [
				2,
				['goto', 'symbol_params']
			],

			'symbol_inner_pointer' => [
				2,
				['goto', 'symbol_inner_symbol']
			]
		];
	}

	protected function whitespace( &$rule ): bool
	{
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
				else if( $this->data[$this->pos] == '\\' && $this->pos + 1 < $this->length &&
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

	protected function group( array &$rule ): bool
	{
		return true;
	}

	/**
	 * Sprawdza czy znak jest równy podanemu.
	 * Jeżeli nie jest równy, przerywa działanie reguły.
	 * Skrót ibchar od inverse break character.
	 * 
	 * @param  array  &$rule Reguła z której pobierany będzie znak.
	 * @return bool          Zwraca TRUE lub FALSE w zależności od tego czy znak jest równy czy nie.
	 */
	protected function ibchar( array &$rule ): bool
	{
		if( $rule[1] == $this->data[$this->pos] )
		{
			$this->pos++;
			return true;
		}

		return false;
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

	protected function char( &$rule ): bool
	{
		if( isset($rule[1][$this->data[$this->pos]]) )
		{
			if( $rule[1][$this->data[$this->pos]] !== false )
				$this->Run( $rule[1][$this->data[$this->pos++]] );
			else
				$this->pos++;

			return false;
		}
		else if( $rule[2] )
		{
			$this->Run( $rule[2] );
			return false;
		}

		return true;
	}

	protected function echar( &$rule ): bool
	{
		if( isset($rule[1][$this->data[$this->pos]]) )
		{
			echo $this->data[$this->pos] . ' => ' . $rule[1][$this->data[$this->pos]] . "\n";

			if( $rule[1][$this->data[$this->pos]] !== false )
				$this->Run( $rule[1][$this->data[$this->pos++]] );
			else
				$this->pos++;
		}
		else if( $rule[2] )
			$this->Run( $rule[2] );

		return true;
	}

	protected function execute( &$rule ): bool
	{
		$this->Run( $rule[1] );
		return true;
	}

	protected function uget( &$rule ): bool
	{
		while( !isset($rule[1][$this->data[$this->pos]]) )
			$this->pos++;

		// pomiń znak gdy podana została wartość true
		if( $rule[1][$this->data[$this->pos]] )
			$this->pos++;

		return true;
	}

	protected function next( &$rule ): bool
	{
		if( $rule[1] == $this->data[$this->pos] )
		{
			$this->pos++;
			if( $rule[2] )
				$this->Run( $rule[2] );
			return false;
		}
		else if ( $rule[3] )
		{
			$this->Run( $rule[3] );
			return false;
		}
		return true;
	}

	protected function goto( &$rule ): bool
	{
		$this->Run( $rule[1] );
		return false;
	}

	protected function string( &$rule ): bool
	{
		if( !preg_match($rule[1], $this->data, $matches, 0, $this->pos) )
			return true;

		$this->pos += strlen( $matches[0] );

		if( isset($rule[2][$matches[0]]) )
		{
			$this->Run( $rule[2][$matches[0]] );
			return false;
		}
		else if( $rule[3] )
		{
			$this->Run( $rule[3] );
			return false;
		}

		return true;
	}

	protected function capture( &$rule ): bool
	{
		if( !preg_match($rule[1], $this->data, $matches, 0, $this->pos) )
		{
			echo 'Match not found!' . "\n";
			echo $this->data[$this->pos] . $this->data[$this->pos + 1] . $this->data[$this->pos + 2] . $this->data[$this->pos + 3] . "\n";

			if( $rule[2] !== false )
				$this->Run( $rule[2] );
			return true;
		}

		echo $matches[0] . "\n";
		
		$this->pos += strlen( $matches[0] );
		$this->results[] = $matches[0];

		return true;
	}

	protected function uskip( &$rule ): bool
	{
		while( $this->length > $this->pos && !isset($rule[1][$this->data[$this->pos]]) )
			$this->pos++;

		return true;
	}

	public function Run( $point = 'entry' )
	{
		echo ">> " . $point . "\n";
		for( $x = 0; $x < 1000000; ++$x)
			$x = $x - 2 + 3;
		$isrun = true;
		$level = 0;

		if( $this->length <= $this->pos )
			return;

		if( !isset($this->rules[$point]) )
			die( "No entry point!\n" );

		$rule = &$this->rules[$point];
		for( $x = 1; $x < $rule[0]; ++$x )
			if( !$this->{$rule[$x][0]}($rule[$x]) )
				break;
	}
}

// $parser = new Parser( './test/moss/tst/array_test.c' );
// $parser = new Parser( './test/main.c' );
// $parser->Run();

$berseker = new Berseker( './test/moss/tst/array_test.c' );
// $berseker = new Berseker( './test/main.c' );
$berseker->Run();

print_r( $berseker->results );

// $parser->ExtractScope( $content );
// $parser->ParseElements();
