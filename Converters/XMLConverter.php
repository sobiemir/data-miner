<?php
namespace ObjectMiner\Converters;

use ObjectMiner\Structures\ElementStructure;
use ObjectMiner\Interfaces\ConverterInterface;

class XMLConverter extends ConverterBase implements ConverterInterface
{
	public $_filename;
	public $_extension;

	public function __construct( array $result, array $rules )
	{
		parent::__construct( $result, $rules );

		$this->_filename  = 'default';
		$this->_extension = 'xml';
	}

	public function Run(): void
	{
		// brak reguł, nic nie rób
		if( count($this->_rules) == 0 )
			return;

		// domyślne ustawienia
		$settings = [
			'filename'  => 'default',
			'extension' => 'xml',
			'pretty'    => false,
			'root'      => [
				'tag' => 'root'
			]
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

		if( !isset($settings['root']['tag']) )
			return;

		$start = '<' . $settings['root']['tag'] . '>';
		$this->_output =
			'<?xml version="1.0" encoding="UTF-8" ?>' .
			($this->_pretty ? "\n" : "") .
			$start .
			($this->_pretty ? "\n" : "") .
			$this->_ConstructElements( $this->_rules, true ) .
			'</' . $settings['root']['tag'] . '>' .
			($this->_pretty ? "\n" : "");
	}

	public function Save( string $path ): void
	{
		echo $this->_output;
	}

	protected function _ConstructElement( array $prepared, ElementStructure $elem, array $value ): string
	{
		$tag    = $value['tag'];
		$multi  = isset( $value['value'] ) && is_array( $value['value'] );
		$inner  = isset( $value['value'] )
			? $value['value']
			: null;

		if( !$this->_CheckRequired($prepared, $elem, $value['required']) )
			return '';

		if( isset($prepared['tag']) )
			$tag = $this->_GetValue( $prepared['tag'], $elem );
		if( isset($prepared['value']) )
			$inner = $this->_GetValue( $prepared['value'], $elem );
		else if( isset($value['value']) && is_array($value['value']) )
			$inner = $this->_ConstructElements( $value['value'] );
		if( !isset($value['value']) && isset($value['values']) )
			$inner = $this->_ConstructElements( $value['values'], true );

		$pretty = $this->_pretty
			? "\n"
			: '';
		$indent = $this->_pretty
			? $this->_indent
			: '';

		return
			"{$indent}<{$tag}" .
			$this->_ConstructAttributes( $prepared, $elem, $value ) .
			($inner
				? '>' .
					($multi
						? "{$pretty}{$inner}{$indent}"
						: $inner
					) .
					"</{$tag}>{$pretty}"
				: " />{$pretty}"
			);
	}

	protected function _ConstructAttributes( array $prepared, ElementStructure $data, array $info ): string
	{
		$content = '';
		foreach( $info as $key => $val )
		{
			if( $key[0] !== '@' )
				continue;

			$attribute = substr( $key, 1 );

			if( isset($prepared[$key]) )
				$element = addslashes( $this->_GetValue($prepared[$key], $data) );
			else
				$element = $info[$key];

			$content .= " {$attribute}=\"{$element}\"";
		}
		return $content;
	}

	protected function _PrepareRequired( string $key, array &$elem ): bool
	{
		// pomiń klucze specjalne
		if( $key[0] == '#' )
			return false;

		// nazwa znacznika jest wymagana
		if( !isset($elem['tag']) )
			return false;

		$required = !isset( $elem['required'] )
			? []
			: (is_array( $elem['required'] )
				? $elem['required']
				: [$elem['required']]
			);

		// znacznik musi mieć jakąś nazwę, warto więc sprawdzić czy ona istnieje
		if( $elem['tag'][0] == '%' && !in_array($elem['tag'], $required) )
			$required[] = 'tag';

		// usuń te, które nie są potrzebne
		foreach( $required as $key => $value )
			if( !isset($elem[$value]) || strlen($elem[$value]) == 0 || $elem[$value][0] != '%' )
				unset( $required[$key] );

		parent::_PrepareRequired( $key, $elem );
		$elem['required'] = $required;

		return true;
	}

	protected function _GetValue( array $prepared, ElementStructure $data ): string
	{
		$current = parent::_ExtractValue( $prepared, $data );

		if( count($current) > 1 )
		{
			$output = '';
			foreach( $current as $single )
				$output .= $current->content;
			return $output;
		}
		return $current[0]->content;
	}
}
