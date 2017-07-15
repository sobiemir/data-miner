
class Parser
{
	private $_file;
	private $_data;
	private $_length;
	private $_pos;
	private $_line;

	public function __construct( string $file )
	{
		$this->_data = file_get_contents( $file );

		// błąd podczas odczytu pliku
		if( $this->_data === false )
			exit;

		$this->_file   = $file;
		$this->_length = strlen( $this->_data );
		$this->_pos    = 0;
	}

	public function NextNonWhiteChar(): void
	{
		do
			++$this->_pos;
		while( $this->_pos < $this->_length && $this->IsWhiteChar($this->_data[$this->_pos]) );
	}

	public function IsWhiteChar( string $char ): bool
	{
		return $char == "\t" || $char == ' ' || $char == "\r" || $char == "\n";
	}

	public function Run(): void
	{
		$this->ParseScope();
	}

	public function ParseScope(): void
	{
		// pobierz pierwszy znak który nie jest znakiem przerwy pomiędzy znakami
		if( $this->IsWhiteChar($this->_data[$this->_pos]) )
			$this->NextNonWhiteChar();

		while( $this->_pos < $this->_length )
		{
			if( !$this->_GetMacro() )
				;
			else if( $this->_GetComment() )
				;
			$this->NextNonWhiteChar();
		}
		// $this->_GetComment();
		// $this->_GetSymbol();
	}

	private function _GetComment(): bool
	{
		if( $this->_data[$this->_pos] != '/' )
			return false;

		if( $this->_data[$this->_pos + 1] == '/' )
		{
			return true;
		}
		else if( $this->_data[$this->_pos + 2] == '*' )
		{
			return true;
		}
		return false;
	}

	private function _GetMacro(): bool
	{
		if( $this->_data[$this->_pos] != '#' )
			return false;

		$this->NextNonWhiteChar();

		if( substr_compare($this->_data, 'include', $this->_pos, 7) === 0 )
		{
			$this->_pos += 7;
			$this->NextNonWhiteChar();

			preg_match( '/[<"]([^>"]+)[>"]*/A', $this->_data, $matches, 0, $this->_pos );
			$this->_pos += strlen( $matches[0] );
		}
		else if( substr_compare($this->_data, 'define', $this->_pos, 6) === 0 )
		{
			$this->_pos += 6;
			$this->NextNonWhiteChar();

			preg_match( '/[$a-zA-Z_][$a-zA-Z_0-9]*/A', $this->_data, $matches, 0, $this->_pos );
			$this->_pos += strlen( $matches[0] );

			$this->_GetMacroArguments();
			$this->_SkipMacroContent();
		}
		else
			$this->_SkipMacroContent();

		return true;
	}

	private function _GetMacroArguments(): ?array
	{
		$arguments = [];

		// aby makro było funkcją, bezpośrednio po identyfikatorze musi występować nawias
		if( $this->_data[$this->_pos] != '(' )	
			return null;

		do
		{
			// pobierz nazwę zmiennej
			// kolejna iteracja pętli to 2 wywołanie funkcji NextNonWhiteChar
			// dzięki temu pomija przecinek, separujący poszczególne argumenty
			$this->NextNonWhiteChar();
			preg_match( '/[$a-zA-Z_][$a-zA-Z_0-9]*/A', $this->_data, $matches, 0, $this->_pos );

			// sprawdź czy znaleziono pasujący wzorzec
			if( isset($matches[0]) )
			{
				$arguments[] = $matches[0];

				$this->_pos += strlen( $matches[0] ) - 1;
				$this->NextNonWhiteChar();
			}
		}
		while( $this->_data[$this->_pos] != ')' );

		return $arguments;
	}

	private function _SkipMacroContent(): void
	{
		while( $this->_data[$this->_pos] != "\r" && $this->_data[$this->_pos] != "\n" )
		{
			$this->_pos++;

			// sprawdź czy przed znakiem nowej linii znajduje się znak ucieczki (escape char)
			if( $this->_data[$this->_pos] == '\\'
				&& ($this->_data[$this->_pos + 1] == "\r" || $this->_data[$this->_pos + 1] == "\n") )
			{
				// jeżeli znak nowej linii składa się z dwóch znaków, pomiń je
				if( $this->_data[$this->_pos + 1] == "\r" && $this->_data[$this->_pos + 2] == "\n" )
					$this->_pos++;
				$this->_pos += 2;
			}
		}
	}

	// private $_elements = [];

	// public function GetElements(): array
	// {
	// 	return $this->_elements;
	// }

	// public function IndexOfNonWhite( string &$data, int $length = 0, int $index = 0 ): int
	// {
	// 	if( $length == 0 )
	// 		$length = strlen( $data );

	// 	for( $x = 0; $x < $length; ++$x )
	// 		if( $data[$x] != ' ' && $data[$x] != "\t" && $data[$x] != "\r" && $data[$x] != "\n" )
	// 			return $index;

	// 	return -1;
	// }

	// public function IsWhiteSpace( string $char )
	// {
	// 	return ($char == "\t" || $char == ' ' || $char == "\n" || $char == "\r");
	// }

	// public function ExtractScope( string &$content ): void
	// {
	// 	$data = '';

	// 	for( $x = 0, $y = strlen($content); $x < $y; ++$x )
	// 	{
	// 		// pomiń białe znaki
	// 		if( $this->IsWhiteSpace($content[$x]) )
	// 			continue;

	// 		// przechwyć makro
	// 		if( $this->CaptureMacro($content, $y, $x, $x) )
	// 			continue;

	// 		// else if( $char == '#' )
	// 		// {
	// 		// 	$x = $this->GetMacro( $content, $y, $x, $data );
	// 		// 	$this->_elements[] = $data;
	// 		// 	$data = '';
	// 		// }
	// 		// else if( $char == '/' )
	// 		// {
	// 		// 	$x = $this->GetComment( $content, $y, $x, $data );
	// 		// 	$this->_elements[] = $data;
	// 		// 	$data = '';
	// 		// }
	// 		// else
	// 		// {
	// 		// 	$x = $this->GetSymbol( $content, $y, $x, $data );
	// 		// 	$this->_elements[] = $data;
	// 		// 	$data = '';
	// 		// }
	// 	}
	// }

	// private function CaptureMacro( string &$data, int $length, int $idx, int &$fidx ): bool
	// {
	// 	if( $data[$idx] != '#' )
	// 		return false;

	// 	// pomiń białe znaki po znaku #
	// 	while( $this->IsWhiteSpace($data[++$idx]) )
	// 		;

	// 	// define lub include
	// 	if( substr_compare($data, 'include', $idx, 7) == 0 )
	// 		$this->_GetInclude( $data );
	// 	else if( substr_compare($data, 'define', $idx, 6) == 0 )
	// 		;

	// 	return true;
	// 	// $itsnotend = false;

	// 	// for( ; $index < $data_length; ++$index )
	// 	// {
	// 	// 	if( $data[$index] == "\r" || $data[$index] == "\n" )
	// 	// 	{
	// 	// 		if( $data[$index] == "\r" && $data[$index + 1] == "\n" )
	// 	// 			++$index;

	// 	// 		if( $itsnotend )
	// 	// 		{
	// 	// 			$itsnotend = false;
	// 	// 			$line     .= "\n";
	// 	// 			continue;
	// 	// 		}
	// 	// 		break;
	// 	// 	}
	// 	// 	else if( $data[$index] == '\\' )
	// 	// 	{
	// 	// 		$itsnotend = true;
	// 	// 		continue;
	// 	// 	}
	// 	// 	else if( $itsnotend )
	// 	// 	{
	// 	// 		$line     .= '\\';
	// 	// 		$itsnotend = false;
	// 	// 	}
	// 	// 	$line .= $data[$index];
	// 	// }
	// 	// return $index;
	// }

	// public function ParseElements(): void
	// {
	// 	// parsuj każdy pobrany wcześniej element
	// 	foreach( $this->_elements as &$elem )
	// 	{
	// 		$length = strlen( $elem );

	// 		// makra
	// 		if( $elem[0] == '#' )
	// 		{
	// 			$index = $this->IndexOfNonWhite( $elem, $length, 1 );

	// 			if( $index == -1 )
	// 				continue;

	// 			// #include
	// 			if( $elem[$index] == 'i' && $elem[$index + 1] == 'n' )
	// 			{
	// 				preg_match( '/^\#[\s]*include[\s]+(?:(?:<([^>]+)>)|(?:"([^"]+)"))/', $elem, $matches );

	// 			}
	// 			// #define
	// 			else if( $elem[$index] == 'd' )
	// 			{
	// 				preg_match( '/^[\s]*\#[\s]*define[\s]+([a-zA-Z_0-9$]*)(?:\(((?:[\s]*(?:[a-zA-Z_0-9$]+|(?:\.\.\.))[\s]*[,]?[\s]*)*)\))?[\s]*(.*)/s', $elem, $matches );
	// 			}
	// 		}
	// 		// pozostałe - nie komentarze
	// 		else if( $elem[0] != '/' )
	// 		{
	// 			preg_match( '/^[\s]*((?:[a-zA-Z_0-9$]*[\s]*)+)(\[[^\]]*\])?(?:\(((?:[\s]*(?:[a-zA-Z_0-9$*]+|(?:\.\.\.))[\s]*[,]?[\s]*)*)\))?[\s]*(=)?[\s]*([^;{]*)(.*)/s', $elem, $matches );
	// 			print_r( $matches );
	// 			$this->DetectSymbol( $matches );
	// 		}
	// 	}
	// }

	// private function DetectSymbol( array &$matches )
	// {
	// 	if( !empty($matches[1]) ) {
	// 		// print_r( explode( ' ', trim($matches[1]) ) );
	// 		// echo "\n";
	// 	}
	// }

	// private function GetSymbol( string &$data, int $data_length, int $index, string &$line ): int
	// {
	// 	$level = 0;

	// 	for( ; $index < $data_length; ++$index )
	// 	{
	// 		if( $data[$index] == '{' )
	// 			++$level;
	// 		else if( $data[$index] == '}' )
	// 		{
	// 			--$level;
	// 			if( $level < 1 )
	// 			{
	// 				$line .= '}';
	// 				++$index;
	// 				break;
	// 			}
	// 		}
	// 		else if( $level == 0 && $data[$index] == ';' )
	// 		{
	// 			$line .= ';';
	// 			++$index;
	// 			break;
	// 		}
	// 		$line .= $data[$index];
	// 	}
	// 	return $index;
	// }

	// private function GetComment( string &$data, int $data_length, int $index, string &$line ): int
	// {
	// 	if( $data[$index + 1] == '*' )
	// 		for( ; $index < $data_length; ++$index )
	// 		{
	// 			if( $data[$index] == '*' && $data[$index + 1] == '/' )
	// 			{
	// 				$line .= '*/';
	// 				++$index;
	// 				break;
	// 			}
	// 			$line .= $data[$index];
	// 		}
	// 	else if( $data[$index + 1] == '/' )
	// 		$index = $this->GetMacro( $data, $data_length, $index, $line );

	// 	return $index;
	// }

	// private function GetMacro( string &$data, int $data_length, int $index, string &$line ): int
	// {
	// 	$itsnotend = false;

	// 	for( ; $index < $data_length; ++$index )
	// 	{
	// 		if( $data[$index] == "\r" || $data[$index] == "\n" )
	// 		{
	// 			if( $data[$index] == "\r" && $data[$index + 1] == "\n" )
	// 				++$index;

	// 			if( $itsnotend )
	// 			{
	// 				$itsnotend = false;
	// 				$line     .= "\n";
	// 				continue;
	// 			}
	// 			break;
	// 		}
	// 		else if( $data[$index] == '\\' )
	// 		{
	// 			$itsnotend = true;
	// 			continue;
	// 		}
	// 		else if( $itsnotend )
	// 		{
	// 			$line     .= '\\';
	// 			$itsnotend = false;
	// 		}
	// 		$line .= $data[$index];
	// 	}
	// 	return $index;
	// }
}