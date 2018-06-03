<?php
namespace ObjectMiner\Converters;

use ObjectMiner\Structures\ElementStructure;
use ObjectMiner\Interfaces\ConverterInterface;

class JSONConverter extends ConverterBase implements ConverterInterface
{
	private $_filename;
	private $_extension;

	public function __construct( array $result, array $rules )
	{
		parent::__construct( $result, $rules );

		$this->_filename  = 'default';
		$this->_extension = 'json';
	}

	public function Run(): void
	{
		// brak reguł, nic nie rób
		if( count($this->_rules) == 0 )
			return;

		// domyślne ustawienia
		$settings = [
			'filename'  => 'default',
			'extension' => 'json',
			'pretty'    => false
		];

		// wyciągnij ustawienia z pliku
		if( isset($this->_rules['#SETTINGS']) )
			foreach( array_keys($settings) as $key )
				if( isset($this->_rules['#SETTINGS'][$key]) )
					$settings[$key] = $this->_rules['#SETTINGS'][$key];

		$this->_pretty    = $settings['pretty'];
		$this->_filename  = $settings['filename'];
		$this->_extension = $settings['extension'];

		parent::_ResetSource();

		$this->_output =
			'{' .
			$this->_ConstructElements( $this->_rules, true ) .
			($this->_pretty ? "\n" : "") .
			'}' .
			($this->_pretty ? "\n" : "");
	}

	public function Save( string $path ): void
	{
		echo $this->_output;
	}

	protected function _ConstructElement( array $prepared, ElementStructure $elem, array $value ): string
	{
		$key    = $value['key'];
		$type   = 'single';
		$multi  = isset( $value['value'] ) && is_array( $value['value'] );
		$inner  = isset( $value['value'] )
			? $value['value']
			: null;

		if( !$this->_CheckRequired($prepared, $elem, $value['required']) )
			return '';

		if( isset($prepared['key']) )
			$key = $this->_GetValue( $prepared['key'], $elem );
		if( isset($prepared['value']) )
		{
			$inner = $this->_GetValue( $prepared['value'], $elem );
			if( is_array($inner) )
				$type  = 'array';
		}
		else if( isset($value['value']) && is_array($value['value']) )
		{
			$type  = 'object';
			$inner = $this->_ConstructElements( $value['value'] );
		}
		else if( !isset($value['value']) && isset($value['values']) )
		{
			$type  = 'object';
			$inner = $this->_ConstructElements($value['values'], true );
		}

		$pretty = $this->_pretty
			? "\n"
			: '';
		$indent = $this->_pretty
			? $this->_indent
			: '';
		$comma = $this->_source['index'] != 0 || $this->_source['position'] != 0
			? ','
			: '';

		return
			"{$comma}{$pretty}{$indent}\"{$key}\": " .
			($inner
				? ($type == 'single'
					? ($inner == "true" || $inner == "false" || is_numeric($inner) || $inner == "null"
						? $inner
						: '"' . $inner . '"'
					)
					: ($type == 'array'
						? "[{$inner}{$pretty}{$indent}]"
						: "{{$inner}{$pretty}{$indent}}"
					)
				)
				: "null{$pretty}"
			);
	}

	protected function _PrepareRequired( string $key, array &$elem ): bool
	{		
		// pomiń klucze specjalne
		if( $key[0] == '#' )
			return false;

		$source = [
			'current'  => null,
			'previous' => $this->_source
		];

		// przygotuj źródło, z którego będą pobierane dane przy konstrukcji elementu
		if( isset($elem['enter']) )
		{
			$current = $this->_source['current']
				? $this->_source['current'][$this->_source['index']]
				: null;
			if( $current )
				$source['current'] =
					isset( $current->subGroups[$elem['enter']] )
						? $current->subGroups[$elem['enter']]
						: null;
			else
				$source['current'] = isset( $this->_result[$elem['enter']] )
					? $this->_result[$elem['enter']]
					: null;

			if( !$source['current'] )
				return false;
		}

		// nazwa i wartość są wymagane
		if( !isset($elem['key']) )
			return false;
		if( !isset($elem['value']) && !isset($elem['values']) )
			return false;

		$required = !isset( $elem['required'] )
			? []
			: (is_array( $elem['required'] )
				? $elem['required']
				: [$elem['required']]
			);

		// znacznik musi mieć jakąś nazwę, warto więc sprawdzić czy ona istnieje
		if( $elem['key'][0] == '%' && !in_array($elem['key'], $required) )
			$required[] = 'key';

		// usuń te, które nie są potrzebne
		foreach( $required as $key => $value )
			if( !isset($elem[$value]) || strlen($elem[$value]) == 0 || $elem[$value][0] != '%' )
				unset( $required[$key] );

		$elem['required'] = $required;

		// zapisz aktualne źródło danych
		if( isset($elem['enter']) )
		{
			$this->_source = $source;

			if( $this->_pretty )
				$this->_indent .= "\t";
		}

		return true;
	}

	protected function _GetValue( array $prepared, ElementStructure $data ): string
	{
		$current = parent::_ExtractValue( $prepared, $data );

		if( count($current) > 1 )
		{
			$output = [];
			foreach( $current as $single )
				$output[] = '"' . addslashes( $current->content ) . '"';
			return implode( "," . $this->_pretty ? "\n" : '', $output );
		}
		return addslashes( $current[0]->content );
	}
}
