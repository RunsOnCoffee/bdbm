#!/usr/bin/php
<?php
ini_set( 'date.timezone', "America/New_York" );
include_once '../Ristretto.lib.php';

use Ristretto\RSLog;

RSLog::configure( array( 'log' => 'test.log', 'type' => RSLog::FILE ) );

RSLog::error( 'error test' );
RSLog::warning( 'warning test', ' awesome' );
RSLog::info( 'info test' );
RSLog::debug( 'debug test' );

RSLog::info( "the ", "time ", "is ", new DateTime );

RSLog::info( "my exception: ", new Exception );

RSLog::info( $_ENV );


RSLog::info( RSLog::sharedInstance() );
?>