import fs = require( "fs" );

class Parser
{
	private _file: string;
	private _data: string;
	private _length: number;
	private _pos: number;

	public constructor( file: string )
	{
		this._file   = file;
		this._data   = fs.readFileSync(file).toString();
		this._length = this._data.length;
		this._pos    = 0;
	}

	public NextNonWhiteChar(): boolean
	{
		do
		{
			if( this._length >= this._pos )
				return false;
			this._pos++;
		}
		while( this.IsWhiteChar(this._data[this._pos]) )
			;
		return true;
	}

	public IsWhiteChar( char: string ): boolean
	{
		return char === "\t" || char === " " || char === "\r" || char === "\n";
	}

	public Run(): void
	{

	}
}

const parser = new Parser( "../test/moss/tst/array_test.c" );
parser.Run();
