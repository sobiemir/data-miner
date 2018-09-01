<?php
namespace ObjectMiner\Interfaces;

use ObjectMiner\Parser;

interface ExtensionInterface
{
	public function __construct( Parser &$parser );
	public function GetMappings(): array;
	public function Run( array &$rule ): bool;
}
