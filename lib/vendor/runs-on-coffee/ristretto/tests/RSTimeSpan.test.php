#!/usr/bin/php
<?php
include_once '../../Ristretto/Ristretto.lib.php';
include 'RSTimeSpan.class.php';

RSLog::configure( null, RSLog::DEBUG );

$e = new RSTimeSpan;
$e->start = new DateTime( '2014-06-26' );
$e->end = (clone $e->start)->modify( "+1 second" );
$e->recurrence = 'daily';
$e->interval = 30;
$e->skipWeekendDays = true;


$events = $e->eventsInRange( new DateTime( '2014-04-01' ), new DateTime );
$dates = array( array('Weekday','Date','Days'));
//foreach( $events as $r ) $dates[] = array( $r->format('l'), $r->format('Y-m-d') );

$skipped = 0;
$f = new DateTime( '2014-06-26' );
$dates[] = array( $f->format('l'), $f->format('Y-m-d') );
for( $i = 0; $i < 24; $i++ )
{
	$nextMonth = (clone $f)->modify("+1 month");
	$daysInNextMonth = $nextMonth->format('t') - $skipped;
	$daysInMonth = $daysInNextMonth - $skipped;
	$skipped = 0;
	
	$f->modify( '+'.$daysInMonth.' days' );
	while( $f->format('l') == 'Saturday' /*|| $next->format('l') == 'Sunday'*/ )
	{
		$f->modify( '+1 days' );
		$skipped++;
	}
	
	$dates[] = array( $f->format('l'), $f->format('Y-m-d'), $daysInMonth );
}



RSArray::textTable( $dates );
?>