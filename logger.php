<?php

abstract class Logger
{
	private static $indent = '';
	private static $indentChars = 2;

	public static function IndentInc()
	{
		Logger::$indent .= str_repeat( ' ', Logger::$indentChars );
	}

	public static function IndentDec()
	{
		Logger::$indent = substr( Logger::$indent, 0, -Logger::$indentChars );
	}

	public static function Log( $message )
	{
		echo '# ' . Logger::$indent . $message . "\n";
	}

	public static function Info( $message )
	{
		$message = '[INF]: ' . Logger::$indent . $message . "\n";
		echo $message;
	}

	public static function Warning( $message )
	{
		$message = '[WAR]: ' . Logger::$indent . $message . "\n";
		echo $message;
	}

	public static function Error( $message )
	{
		$message = '[ERR]: ' . Logger::$indent . $message . "\n";
		echo $message;
	}
}
