<?php
require_once './php/Parser.php';

Logger::SetLogLevel( ZLOG_NONE );

$grc = new Parser( 'gramma/c.json' );
$grc->GetRulesFromFile( 'gramma/json_strip_comments.json' );
$grc->UnificateLineEndings();
$grc->Run();

$parser = new Parser( './test/main.c' );
$parser->GetRulesFromString( $grc->data );
$parser->UnificateLineEndings();
$parser->Run();

// var_dump($parser->data);

// $berseker = new Berseker( './test/moss/tst/array_test.c' );
// $berseker = new Berseker( './test/main.c' );

// $berseker->GetRulesFromFile( 'gramma/c.json' );
// $berseker->UnificateLineEndings();
// $berseker->Run( 'ENTRY' );

// echo $berseker->data;
// var_dump( $berseker->results );
// file_put_contents('asdf.txt', $berseker->data);

// file_put_contents('asdfg.txt', print_r($berseker->results, true));
// file_put_contents('asdfgh.txt', print_r($berseker->storage, true));

// $parser->ExtractScope( $content );
// $parser->ParseElements();
