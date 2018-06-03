<?php
namespace ObjectMiner\Interfaces;

interface ConverterInterface
{
	public function __construct( array $result, array $rules );
	public function Run(): void;
	public function Save( string $path ): void;
	public function GetOutput(): string;
}
