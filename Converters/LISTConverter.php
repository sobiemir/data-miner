<?php
namespace ObjectMiner\Converters;

use ObjectMiner\Structures\ElementStructure;
use ObjectMiner\Interfaces\ConverterInterface;

class LISTConverter extends ConverterBase implements ConverterInterface
{
	public function Run(): void
	{
		if( count($this->_rules) == 0 )
			return;

		$this->_pretty = true;

		parent::_ResetSource();
		$this->_output = $this->_ConstructElements( $this->_rules, true );
	}

	public function Save( string $path ): void
	{
		echo $this->_output;
	}

	protected function _ConstructElement( array $prepared, ElementStructure $elem, array $value ): string
	{
		$key    = $value['key'];
		$type   = 'object';
		$multi  = isset( $value['value'] ) && is_array( $value['value'] );
		$inner  = isset( $value['value'] ) ? $value['value'] : null;

		if( !$this->_CheckRequired($prepared, $elem, $value['required']) )
			return '';

		if( isset($prepared['key']) )
			$key = $this->_GetValue( $prepared['key'], $elem );
		if( isset($prepared['value']) )
		{
			$inner = $this->_GetValue( $prepared['value'], $elem );
			$type = 'single';
		}
		else if( $multi )
			$inner = $this->_ConstructElements( $value['value'] );
		else if( !isset($value['value']) && isset($value['values']) )
			$inner = $this->_ConstructElements($value['values'], true );

		$index  = '';
		$prev   = $this->_source;

		do
			$index = ($prev['position'] + 1) . '.' . $index;
		while( ($prev = $prev['previous'])['previous'] != null );

		return
			"{$this->_indent}{$index} {$key}: " .
			($type == 'single' ? $inner . "\n" : "\n{$inner}");
	}

	protected function _PrepareRequired( string $key, array &$elem ): bool
	{
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

		$striptab = $this->_source['previous'] == null;
		parent::_PrepareRequired( $key, $elem );
		if( $striptab )
			$this->_indent = '';

		$elem['required'] = $required;
		return true;
	}
}
