<?php
namespace ObjectMiner\Extensions;

use ObjectMiner\Parser;
use ObjectMiner\Interfaces\ExtensionInterface;

class SleepExtension implements ExtensionInterface
{
	private $_parser = null;

	public function __construct( Parser &$parser )
	{
		$this->_parser = $parser;
	}

	public function GetMappings(): array
	{
		return [
			"SLEEP" => [
				[], "", 1, [ -1 ]
			]
		];
	}

	/**
	 * Usypia działanie skryptu na podaną ilość sekund.
	 *
	 * CODE:
	 *     $this->Run( ['', 1] );
	 *
	 * PARAMETERS:
	 *     $rule Tablica z argumentami dla reguły.
	 *           - 0 > Indeks zarezerwowany.
	 *           - 1 > Ilość sekund uśpienia skryptu.
	 *
	 * RETURNS:
	 *     Zawsze wartość TRUE.
	 */
	public function Run( array &$rule ): bool
	{
		sleep( $rule[1] );
		return true;
	}
}
