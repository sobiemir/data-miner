<?php
// require_once './php/wierch/Parser.php';

// $wierch = new \Wierch\Parser();
// $wierch->setDirectory( './doc/' );

// $wierch->parse( 'toc' );

// print_r( $wierch->getFiles() );

require_once './php/Logger.php';
require_once './php/ObjectMiner.php';

use ObjectMiner\ObjectMiner;
use ObjectMiner\Logger;

Logger::SetLogLevel( ZLOG_WARNING | ZLOG_ERROR );
$parser = new ObjectMiner();
$parser->Run( './test.ini', 'ini' );
$parser->Save();

// $grc = new Parser( 'gramma/ini.json' );
// $grc->GetRulesFromFile( 'gramma/json_strip_comments.json' );
// $grc->UnificateLineEndings();
// $grc->Run();

// // var_dump($grc->data);

// $parser = new \ObjectMiner\Parser( './test.ini' );
// $parser->GetRulesFromString( $grc->data );
// $parser->UnificateLineEndings();
// $parser->Run();
// $parser->Save();

// $results = $parser->results;

// print_r($results);

//var_dump($results['COMMENT_BLOCK']);

// $data = '';
// foreach( $results['SYMBOL'] as $symbol )
// {
// 	if( $symbol->content != '' )
// 		continue;

// 	$sub = $symbol->subGroups;
// 	$types = [];
// 	$parameters = [];

// 	if( isset($sub['MODIFIERS']) )
// 	{
// 		foreach( $sub['MODIFIERS'] as $modifier )
// 		{
// 			if( $modifier->content == '' )
// 				break;

// 			$pointers = $modifier->subGroups["POINTER"];

// 			$types[] = $modifier->content . (
// 				isset($pointers[0])
// 					? $pointers[0]->content
// 					: ''
// 			);
// 		}
// 	}
// 	if( isset($sub['PARAMETERS']) )
// 	{
// 		foreach( $sub['PARAMETERS'] as $parameter )
// 		{
// 			if( $parameter->content != '' )
// 				break;

// 			$param = [];
// 			$dat = $parameter->subGroups;

// 			if( isset($dat['MODIFIERS']) )
// 			{
// 				foreach( $dat['MODIFIERS'] as $modifier )
// 				{
// 					if( $modifier->content == '' )
// 						break;

// 					$pointers = $modifier->subGroups["POINTER"];

// 					$param[] = $modifier->content . (
// 						isset($pointers[0])
// 							? $pointers[0]->content
// 							: ''
// 					);
// 				}
// 			}

// 			$parameters[] = join($param, ' ');
// 		}
// 	}

// 	if(count($parameters) == 0)
// 		continue;

// 	echo '.. c:function:: ';
// 	echo (join($types, ' '));
// 	if (count($parameters) > 0)
// 	{
// 		echo '( ';
// 		echo join($parameters, ', ');
// 		echo ' )';
// 	}
// 	echo "\n";
// }

// file_put_contents('asdf.txt', $parser->data);
// file_put_contents('asdfg.txt', print_r($parser->results, true));
// file_put_contents('asdfgh.txt', print_r($parser->storage, true));

// var_dump($parser->results);

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
