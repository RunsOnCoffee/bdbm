<?php
/**************************************************************************//**
 * Bluetooth Battery Monitor
 * © 2020 Nicholas Costa.
 *****************************************************************************/
ini_set( 'date.timezone', "America/New_York" );
set_time_limit(0);

define( 'WARNINGLEVEL', 25 );			// default warning level for battery charge

require __DIR__ . '/../lib/vendor/autoload.php';

use Ristretto\RSLog;
use Ristretto\RSPath;

$doc = <<<DOC
Bluetooth Device Battery Monitor

Usage:
  bdbm [-w|--warn PCT]
  bdbm (-h|--help)
  bdbm --version

Options:
  -h --help      Show this screen.
  -w --warn PCT  Raise a warning if a charge level is below PCT   
  --version      Show version.

DOC;

try
{
	$options = Docopt::handle($doc, array('version'=>'Bluetooth Device Battery Monitor 0.3'));

	$devices = getBatteryStatus();
	if( empty( $devices ) ) exit( "No Bluetooth devices found.\n" );
	
	$warnLevel = WARNINGLEVEL;
	if( !empty( $options['--warn'] ) ) $warnLevel = $options['--warn'][0];
	RSLog::debug( "Warn level: $warnLevel%" );
		
	foreach( $devices as $d => $b )
	{
		$len = max( array_map('strlen', array_keys( $devices ) ) );
		echo str_pad( $d, $len+3 ).$b."%\n";
		if( $b < $warnLevel ) showNotification( "Bluetooth Battery Monitor",
			"Battery charge for $d is at ${b}%." );
	}
}
catch( Exception $e )
{
	RSLog::error( 'Exception: '.$e->getMessage() );	
}


/**************************************************************************//**
 * Retrieves the battery status of any Bluetooth interface devices.
 *****************************************************************************/
function getBatteryStatus()
{
	
	$cmd = "ioreg -rlc \"AppleHSBluetoothInterface\" | egrep '(\"Product\"|\"BatteryPercent\")'";
	RSLog::debug( "Command: $cmd" );
	
	ob_start();
	passthru( $cmd );
	$result = ob_get_contents();
	ob_end_clean();

	if( preg_match_all( '/"(Product|BatteryPercent)" = ([0-9]{1,3}|".*?")/msi', $result, $match ) == 0)
	{
		return array();
	}
	
	$devices = array();
	for( $i = 0; $i < count( $match[1] ); $i++ )
	{
		if( $match[1][$i] == 'Product' ) $dev = trim( $match[2][$i], '"' );
		else if( $match[1][$i] == 'BatteryPercent' ) $devices[ $dev ] = $match[2][$i];
	}

	return $devices;
}

/**************************************************************************//**
 * Displays an OS X notification using AppleScript.
 *****************************************************************************/
function showNotification( $title, $msg )
{
	$script = 'display notification "'.$msg.'" with title "'.$title.'"';
	RSLog::debug( 'AppleScript: ', $script );

	$cmd = "osascript -e '$script'";
	exec( $cmd );
}

?>