<?php
namespace ObjectMiner\Converters;

use ObjectMiner\Structures\ElementStructure;

class ConverterBase
{
	protected $_rules;
	protected $_result;
	protected $_source;
	protected $_indent;
	protected $_output;
	protected $_pretty;

	public function __construct( array $result, array $rules )
	{
		$this->_result = $result;
		$this->_rules  = $rules;
		$this->_indent = "";
		$this->_output = "";
		$this->_pretty = false;
		$this->_source = [
			'current'  => null,
			'previous' => null,
			'index'    => 0,
			'position' => 0
		];
	}

	public function GetOutput(): string
	{
		return $this->_output;
	}

	protected function _ResetSource()
	{
		$this->_source = [
			'current'  => null,
			'previous' => null,
			'index'    => 0,
			'position' => 0
		];
	}

	protected function _ConstructElements( array $rules, bool $many = false ): string
	{
		$output = '';

		if( !$many )
			$rules = ['MAIN' => $rules];

		$position = 0;
		foreach( $rules as $key => $value )
		{
			if( !$this->_PrepareRequired($key, $value) )
				continue;

			// sprawdź czy element wszedł do grupy
			$inside   = isset( $value['enter'] );
			$prepared = $this->_PrepareValues( $value );

			if( $inside )
			{
				$this->_source['position'] = $position;
				foreach( $this->_source['current'] as $index => $elem )
				{
					$this->_source['index'] = $index;
					$output .= $this->_ConstructElement( $prepared, $elem, $value );
					$this->_source['position']++;
				}
				$this->_AfterSectionConstruct();

				$position = $this->_source['position'];
				$this->_source = $this->_source['previous'];
			}
			else
				;
		}
		return $output;
	}

	protected function _ConstructElement( array $prepared, ElementStructure $elem, array $value ): string
	{
		return '';
	}

	protected function _PrepareValues( array $elem )
	{
		$output = [];

		foreach( $elem as $key => $value )
		{
			// pomiń klucze specjalne
			if( $key[0] == '#' )
				continue;

			// sprawdź czy wartość ma być wartością pobieraną
			if( gettype($value) != 'string' || strlen($value) == 0 || $value[0] != '%' )
				continue;

			// podziel wartości na części
			$data = explode( '.', $value );
			$index = [];

			// szukaj indeksów, podawanych po dwukropku
			foreach( $data as $k => $v )
			{
				$idx = strpos( $v, ':' );
				if( $idx === false )
				{
					$index[] = null;
					continue;
				}

				// jeżeli indeks zostanie znaleziony, wyziel go
				$data[$k] = substr( $v, 0, $idx );
				$index[]  = substr( $v, $idx + 1 );
			}

			$output[$key] = [
				'parts' => $data,
				'count' => count( $data ),
				'index' => $index
			];
		}
		return $output;
	}

	protected function _PrepareRequired( string $key, array &$elem ): bool
	{
		$source = [
			'current'  => null,
			'previous' => $this->_source,
			'index'    => 0,
			'position' => 0
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

		// zapisz aktualne źródło danych
		if( isset($elem['enter']) )
		{
			$this->_source = $source;

			if( $this->_pretty )
				$this->_indent .= "\t";
		}

		return true;
	}

	protected function _AfterSectionConstruct(): void
	{
		if( $this->_pretty )
			$this->_indent = substr( $this->_indent, 0, strlen($this->_indent) - 1 );
	}

	protected function _CheckRequired( array $prepared, ElementStructure $elem, array $required ): bool
	{
		foreach( $required as $key )
			if( !isset($prepared[$key]) || $this->_GetValue($prepared[$key], $elem) == '' )
				return false;
		return true;
	}

	protected function _GetValue( array $prepared, ElementStructure $data ): string
	{
		$current = $this->_ExtractValue( $prepared, $data );

		if( count($current) > 1 )
		{
			$output = [];
			foreach( $current as $single )
				$output[] = $current->content;
			return implode( ' , ', $output );
		}
		return $current[0]->content;
	}

	protected function _ExtractValue( array $prepared, ElementStructure $data ): array
	{
		$current = $data;
		foreach( $prepared['parts'] as $key => $part )
		{
			if( $part == '%' )
				continue;

			if( isset($current->subGroups[$part]) )
				$current = $current->subGroups[$part];
			else
				return '';

			if( $prepared['index'][$key] != null )
			{
				$index = $prepared['index'][$key];
				$index = $index < 0
					? count($current) + ($index + 1)
					: $index;

				if( is_array($current) && isset($current[$prepared['index'][$key]]) )
					$current = $current[$prepared['index'][$key]];
			}
		}
		return is_array( $current )
			? $current
			: [$current];
	}
}
