import re

class Parser:
	elements = []

	# public function indexOfNonWhite( string &$data, int $length = 0, int $index = 0 ): int
	# {
	# 	if( $length == 0 )
	# 		$length = strlen( $data );

	# 	for( $x = 0; $x < $length; ++$x )
	# 		if( $data[$x] != ' ' && $data[$x] != "\t" && $data[$x] != "\r" && $data[$x] != "\n" )
	# 			return $index;

	# 	return -1;
	# }

	def getMacro( self, data, length, index ):
		itsnotend = False
		line      = ''

		while( index < length ):
			if( data[index] == "\r" or data[index] == "\n" ):
				if( data[index] == "\r" and data[index + 1] == "\n" ):
					index += 1
				if( itsnotend ):
					itsnotend = False
					line     += "\n"
					index    += 1
					continue
				break

			elif( data[index] == '\\' ):
				itsnotend = True
				continue

			elif( itsnotend ):
				line     += '\\'
				itsnotend = False

			line  += data[index]
			index += 1

		return [index, line]

	def getComment( self, data, length, index ):
		line = ''

		if( data[index + 1] == '*' ):
			while( index < length ):
				if( data[index] == '*' and data[index + 1] == '/' ):
					line  += '*/'
					index += 1
					break

				line  += data[index]
				index += 1

		elif( data[index + 1] == '/' ):
			[index, line] = self.getMacro( data, length, index )

		return [index, line]

	def getSymbol( self, data, length, index ):
		level = 0
		line  = ''

		while( index < length ):
			if( data[index] == '{' ):
				level += 1
			elif( data[index] == '}' ):
				level -= 1
				if( level < 1 ):
					line += '}'
					index += 1
					break

			elif( level == 0 and data[index] == ';' ):
				line  += ';'
				index += 1
				break

			line  += data[index]
			index += 1

		return [index, line]

	def extractScope( self, content ):
		length = len( content )
		index  = 0

		while( index < length ):
			char = content[index]
			line = ''

			if( char == "\t" or char == ' ' or char == "\n" or char == "\r" ):
				index += 1
				continue
			elif( char == '#' ):
				[index, line] = self.getMacro( content, length, index )
				self.elements.append( line )
			elif( char == '/' ):
				[index, line] = self.getComment( content, length, index )
				self.elements.append( line )
			else:
				[index, line] = self.getSymbol( content, length, index )
				self.elements.append( line )

			index += 1

	def parseElements( self ):
		for elem in self.elements:
			if( elem[0] == '#' ):
				self.parseMacro( elem )

	def parseMacro( self, elem ):
		print( re.findall( r'^\#[\s]*include[\s]+(?:(?:<([^>]+)>)|(?:"([^"]+)"))', elem ) )

with open( './test/moss/tst/array_test.c', 'r' ) as f:
	read_data = f.read()

	parser = Parser()
	parser.extractScope( read_data )
	parser.parseElements()
