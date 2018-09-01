<?php
namespace ObjectMiner;

define( 'ZLOG_NONE',    0x0 );
define( 'ZLOG_ERROR',   0x1 );
define( 'ZLOG_WARNING', 0x2 );
define( 'ZLOG_INFO',    0x4 );
define( 'ZLOG_NOTICE',  0x8 );
define( 'ZLOG_ALL',     0xF );

/**
 * Klasa zapisu i wyświetlania wiadomości.
 * Dzięki temu można w prosty sposób wyłączyć wiadomości o określonym typie.
 * Pozwala na wyświetlanie notatek, informacji, ostrzeżeń i błędów.
 * Dodatkowo posiada możliwość dołączania wcięcia do wiadomości.
 */
abstract class Logger
{
	/**
	 * Wcięcie dołączane do wiadomości.
	 */
	private static $_indent = '';

	/**
	 * Ilość znaków przypadających na jedno wcięcie.
	 */
	private static $_indentChars = 2;

	/**
	 * Poziom wyświetlanych wiadomości.
	 */
	private static $_logLevel = ZLOG_ALL;

	/**
	 * Wariant wyświetlanych danych.
	 */
	private static $_logConsole = true;

	/**
	 * Plik do którego zapisywane będą wiadomości.
	 */
	private static $_logFile = null;

	/**
	 * Prefiksy wiadomości, wyświetlanych w konsoli i pliku.
	 */
	private static $_levelPrefixes = [
		ZLOG_ERROR   => "ERROR: ",
		ZLOG_WARNING => "WARNING: ",
		ZLOG_INFO    => "INFO: ",
		ZLOG_NOTICE  => ""
	];

	/**
	 * Ustawia poziom wyświetlanych wiadomości w aplikacji.
	 *
	 * DESCRIPTION:
	 *     W przypadku gdy poziom nie będzie zdefiniowany, wszystkie wiadomości
	 *     przypisane do niego zostaną pominięte.
	 *     Lista możliwych poziomów:
	 *     - ZLOG_ALL: wyświetla wszystkie wiadomości.
	 *     - ZLOG_ERROR: wyświetla tylko błędy.
	 *     - ZLOG_WARNING: wyświetla tylko ostrzeżenia.
	 *     - ZLOG_INFO: wyświetla tylko informacje.
	 *     - ZLOG_NOTICE: wyświetla tylko notatki.
	 *     - ZLOG_NONE: nie wyświetla żadnych wiadomości.
	 *     Poziomy można łączyć, można więc podać wartość LOG_ERR | LOG_INFO aby wyświetlać tylko błędy i informacje.
	 *
	 * PARAMETERS:
	 *     level: Poziom dopuszczonych do wyświetlenia wiadomości.
	 */
	public static function SetLogLevel( int $level ): void
	{
		Logger::$_logLevel = $level;
	}

	/**
	 * Ustawia wyjście dla wszystkich wiadomości.
	 *
	 * DESCRIPTION:
	 *     Wiadomości można wyświetlać w konsoli lub zapisywać do pliku.
	 *     W przypadku gdy miejscem docelowym ma być konsola, należy podać
	 *     w pierwszym parametrze wartość TRUE.
	 *     Aby wiadomości zapisywać do pliku, należy w drugim parametrze
	 *     podać jego ścieżkę, w przeciwnym wypadku zaś wartość NULL.
	 *
	 * PARAMETES:
	 *     variant: Wariant wyświetlanych wiadomości.
	 *     file:    Nazwa pliku do którego zapisywane będą wiadomości.
	 */
	public static function SetLogOutput( bool $console, string $file ): void
	{
		Logger::$_logConsole = $variant;
		Logger::$_logFile    = $file;
	}

	/**
	 * Zwiększa wcięcie w kolejnych wiadomościach.
	 */
	public static function IndentInc(): void
	{
		Logger::$_indent .= str_repeat(
			' ',
			Logger::$_indentChars
		);
	}

	/**
	 * Zmniejsza wcięcie w kolejnych wiadomościach.
	 */
	public static function IndentDec(): void
	{
		Logger::$_indent = substr(
			Logger::$_indent,
			0,
			-Logger::$_indentChars
		);
	}

	/**
	 * Zapisuje wiadomość do pliku lub wyświetla ją w konsoli.
	 *
	 * DESCRIPTION:
	 *     Funkcja sprawdza czy wiadomość ma być wyświetlona (co zależy od
	 *     tego jaki stopień wyświetlania wiadomości użytkownik włączył).
	 *     Domyślnie wyświetlane są wszystkie poziomy.
	 *     Dostępne poziomy wypisane zostały w funkcji SetLogLevel.
	 *
	 * PARAMETERS:
	 *     msg:   Wiadomość do wyświetlenia.
	 *     level: Poziom (typ) wyświetlanej wiadomości.
	 */
	public static function Log( string $msg, int $level = ZLOG_NOTICE ): void
	{
		if( !(Logger::$_logLevel & $level) )
			return;

		// utwórz treść wiadomości
		$msg = Logger::$_levelPrefixes[$level] . Logger::$_indent . $msg . "\n";

		// wyświetl wiadomość w konsoli
		if( Logger::$_logConsole )
			echo $msg;

		// zapisz wiadomość do pliku
		if( Logger::$_logFile )
		{
			// logowanie do pliku
		}
	}
}
