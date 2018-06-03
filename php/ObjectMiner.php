<?php
namespace ObjectMiner;

class ObjectMiner
{
	private $_parser = null;
	private $_converters = [];

	public function __construct()
	{
		spl_autoload_register( __NAMESPACE__ . '\\ObjectMiner::Autoloader' );
	}

	public function Run( string $file, string $rules ): void
	{
		$parser = new Parser( __DIR__ . "/Rules/{$rules}.json" );
		$parser->GetRulesFromFile( __DIR__ . '/Rules/json_clear.json' );
		$parser->UnificateLineEndings();
		$parser->Run();

		$this->_parser = new Parser( $file );
		$this->_parser->LoadExtensions();
		$this->_parser->GetRulesFromString( $parser->data );
		$this->_parser->UnificateLineEndings();
		$this->_parser->Run();

		$this->_converters = $this->_parser->GetConverters();
	}

	public function Save( string $path = './' ): void
	{
		if( $this->_parser == null )
			return;

		// sprawdź czy zdefiniowany zostały w pliku reguły do konwertera
		if( count($this->_converters) == 0 )
			return;

		$keys = array_keys( $this->_converters );
		foreach( $keys as $name )
		{
			$key_name  = strtoupper( $name );
			$class     = "\\ObjectMiner\\Converters\\{$key_name}Converter";
			$converter = new $class( $this->_parser->results, $this->_converters[$key_name] );

			$converter->Run();
			$converter->Save( $path );
		}
	}

	public static function Autoloader( string $name ): void
	{
		$parts = explode( '\\', $name );
		$count = count( $parts );

		if( $count > 1 && $parts[0] == 'ObjectMiner' )
			unset( $parts[0] );

		$path = __DIR__ . '/' . implode( '/', $parts ) . '.php';

		if( !file_exists($path) )
			throw new \Exception( "Object '{$name}' not exist in current context, path '{$path}'!" );

		require_once $path;
	}
}
