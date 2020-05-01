#!/usr/bin/php
<?php
$startTime = microtime( true );
include_once "../src/RSProfiler.php";
use Ristretto\RSProfiler;

//$myGlobal = 'blah';

function myFunc()
{
	global $myGlobal;
	if( isset( $myGlobal ) ) echo "$myGlobal\n";
	
	debugger();
}


function debugger( )
{	
	$f = (debug_backtrace( false, 2 ))[1];
	echo $f['function'].'() in '.basename($f['file']).':'.$f['line']."\n";
}

RSProfiler::startProfiler();
RSProfiler::mark( 'Start' );
//RSProfiler::clock( 'Sleep Test' );

for( $i = 0; $i<2000; $i++) usleep( 1000 );

//sleep( 2 );


myFunc();

RSProfiler::clock( 'Sleep Test' );

RSProfiler::mark( 'End' );

RSProfiler::stopProfiler();

print_r( RSProfiler::report() );
?>