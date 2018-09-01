<?php
namespace ObjectMiner\Extensions;

use ObjectMiner\Parser;
use ObjectMiner\Interfaces\ExtensionInterface;

class EchoExtension implements ExtensionInterface
{
	private $_parser = null;
	private $_mappings = [
		"ECHO"         => [[             ], "EchoFunction",     1, [ -1    ]],
		"ECHO_CHAR"    => [[ false       ], "EchoCharFunction", 2, [ -1, 0 ]],
		"ECHO_CURRENT" => [[    -1, true ], "EchoCharFunction", 2, [  0, 1 ]]
	];

	public function __construct( Parser &$parser )
	{
		$this->_parser = $parser;
	}

	public function GetMappings(): array
	{
		return $this->_mappings;
	}

	public function Run( &$rule ): bool
	{
		$key = $rule[0][0];
		if( !isset($this->_mappings[$key]) )
			return false;

		$name = $this->_mappings[$key][1];
		return $this->{$name}( $rule );
	}

	private function EchoFunction( &$rule ): bool
	{
		echo $rule[1] . "\n";
		return true;
	}

	private function EchoCharFunction( &$rule ): bool
	{
		if( $rule[2] )
			echo $this->_parser->ReadableChar(
				$this->_parser->data[$this->_parser->pos]
			) . "\n";
		else
		{
			$pos = $rule[1] < 0
				? $this->_parser->length + $rule[1]
				: $rule[1];
			if( $pos > $this->_parser->length || $pos < 0 )
				$pos = 0;
			echo $this->_parser->ReadableChar(
				$this->_parser->data[$pos]
			) . "\n";
		}
		return true;
	}
}
