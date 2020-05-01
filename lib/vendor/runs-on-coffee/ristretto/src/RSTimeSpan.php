<?php
/*
 * Ristretto - a general purpose PHP object library
 * Copyright (c) 2019 Nicholas Costa. All rights reserved.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 */

namespace Ristretto;

/*! \mainpage RSTimeSpan Documentation
 *
 * \section intro_sec Introduction
 *
 * RSArray is a utility class adding basic array functions for general
 * use in PHP applications.
 *
 * \section log_sec Development Log
 * - December 24, 2017: added max-width support.
 * - November 26, 2017: added buffered output support.
 * - November 24, 2017: added support for associative arrays.
 * - October 28, 2017: added support for column wrapping and passing an options array. 
 * 
 * \section reference_sec Reference
 * https://stackoverflow.com/questions/4746079/how-to-create-a-html-table-from-a-php-array<br />
 * https://stackoverflow.com/questions/2203437/how-to-get-linux-console-columns-and-rows-from-php-cli<br />
 * 
 * ###Color###
 * https://gist.github.com/bpanahij/5962568<br />
 * http://bixense.com/clicolors/<br />
 * http://www.perpetualpc.net/6429_colors.html#color_list<br />
 */

/**
 * Representation of an event with a defined start and end time. Supports multiple
 * recurrence mechanisms.
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 */
class RSTimeSpan
{
	public	$start;
	public	$end;
	public	$recurrence;
	
	public	$interval;
	public	$unit;
	public	$skipWeekendDays;
	
	protected	$recur_until;
	protected	$recur_count;
	
	/**
	 * Constructs an HTML table from the supplied array.
	 *
	 * @param array $array
	 * @return RSConfiguration
	 */
	public static function htmlTable( $array )
	{
	}
	
	/**
	 * Creates a text table from the supplied array.
	 * 
	 * @todo Add support for colors
	 * @param array $array
	 * @param array $options
	 */
	public static function textTable( $array, $options = null )
	{
	}

	public function starts()
	{
		return $this->start;
	}

	public function stops()
	{
		return $this->end;
	}
	
	public function setStart( $s )
	{
		$this->start = $s;
	}
	
	public function setStop( $e )
	{
		$this->end = $e;
	}
	
	public function setRecursUntil( $r )
	{
		$this->recur_until = $r->format( 'Y-m-d' );
	}

	public function eventsInRange( $start, $end )
	{		
		$dates = array();
		$intervals = ( isset( $this->interval ) ) ? $this->interval : 1;
		$pattern = 'normal';
		switch( $this->recurrence )
		{
			// return this event for events which occur once
			case 'once':
				$e = clone $this;
				return array( $e );
				break;
				
			case 'daily':
				$timeUnit = 'day';
				break;

			case 'weekly':
				$timeUnit = 'week';
				break;

			case 'biweekly':
				$timeUnit = 'week';
				$intervals = 2;
				break;

			case 'monthly':
				$timeUnit = 'month';
				break;
			
			case 'monthly_dow':
				$timeUnit = 'day';
				$intervals = 28;
				$pattern = 'positive_week_dow';
				break;

			case 'yearly':
				$timeUnit = 'year';
				break;
		}

		if( $timeUnit === null )
		{
			//SMDebugger::log( "Recurrence '".$this->recurrence."' not found." );
			return array();
		}

		// check end of recurrence
		if( ( $until = $this->recursUntil() ) !== null
			&& $until->format('U') < $end->format('U') ) $end = $until;
		else if( $this->recur_count !== null )
		{
			$recurEnd = $this->starts();
			$recurEnd->modify( '+'.( $intervals * ( $this->recur_count - 1 ) ).' '.$timeUnit );
			
			if( $recurEnd->format('U') < $end->format('U') ) $end = $recurEnd;
		}

		$interval = $intervals.' '.$timeUnit;
		$dates = $this->generate_recurrences( $pattern, $interval, $this->starts(), $start, $end );
		
		return $dates;
	}
	
	protected function recursUntil()
	{
		if( isset( $this->recurs_until ) ) return $this->recurs_until;
		return null;
	}

	public function nextStarts()
	{
		
	}

	/**
	 * Generate the set of all recurrences of this event occurring between specified
	 * dates.
	 *
	 * @todo Rewrite to avoid date calculations based on seconds; needs to calculate 
	 *		each recurrence individually, test for match with interval, and return.
	 * 
	 * @param pattern
	 * @param $duration a string specifying the recurrence of the format "<amount> <unit>"
	 * @param $date the date from which to begin recurring
	 * @param $start the beginning of the range in which to seek recurrent events
	 * @param $end the end of the range in which to seek recurrent events
	 */
	protected function generate_recurrences( $pattern, $duration, $date, $start, $end )
	{		
		// split up the duration
		list( $expr, $unit ) = explode( " ", $duration );
		RSLog::debug( "Expression: $expr, Unit: $unit" );
		
		$next = clone $date;
		$intervalsBetween = $this->intervals_between( $date, $start, $duration );
		RSLog::debug( "Intervals: $intervalsBetween" );
		$next->modify( '+'.( $expr * ceil( $intervalsBetween ) ).' '.$unit );
//		$next->modify( '+'.$expr.' '.$unit );
		
		$recurrences = array();
		if( $pattern == 'positive_week_dow' || $pattern == 'negative_week_dow' )
		{
			// in case the first recurrence is before $next but after $start
			$next->modify( '-'.(2 * $expr).' '.$unit );
			
			while( true )
			{
				// prevent an infinite loop
				$testDate = clone $next;
				$testDate->modify( '+'.$expr.' '.$unit );
				if( $duration == '28 day' && $testDate->format('m') == $next->format('m') )
					$next->modify( '+'.$expr.' '.$unit );
				
				$next->modify( '+'.$expr.' '.$unit );
	
				// ensure month is correct for yearly events
// 				if( $duration == '364 days' )
// 				{
// 					while( $next->format('m') != $date->format('m') )
// 						$next->modify( '+'.( $date->format('m') - $next->format('m') % 12 * 28 ).' days' );
// 				}
				
				if( $pattern == 'positive_week_dow' )
				{
					$next->modify( '+'.( ( ceil($date->format('d') / 7) - ceil($next->format('d') / 7) ) * 7 ).' days' );
				}
// 				else
// 				{
// 	//         next_date := next_date
// 	//           + (CEIL((1 + extract(day from next_date + '1 month'::interval - next_date) - extract(day from next_date)) / 7)
// 	//             - CEIL((1 + extract(day from original_date + '1 month'::interval - original_date) - extract(day from original_date)) / 7))
// 	//           * '7 days'::interval;
// 					$modNext = clone $next;
// 					$modNext->modify( '+1 month' );
// 					$modNext->modify( '-'.$next->format('U').' seconds' );
// 					$modDate = clone $date;
// 					$modDate->modify( '+1 month' );
// 					$modDate->modify( '-'.$date->format('U').' seconds' );
// 					
// 					$next->modify( '+'.(ceil(1 + $modNext->format('d') - $next->format('d'))
// 						- ceil( 1 + $modDate->format('d') - $date->format('d')) * 7).' days' );
// 				}
				
				// check if it's time to leave the loop
				if( $next->format('U') >= $end->format('U') ) break;
				
				if( $next->format('U') < $start->format('U') || $next->format('U') < $date->format('U') ) continue;
				$recurrences[] = clone $next;
			}
		}
		else
		{
			while( $next->format('U') <= $end->format('U') )
			{
				$recurrences[] = clone $next;
				$next->modify( '+'.$expr.' '.$unit );
				if( $this->skipWeekendDays )
				while( $next->format('l') == 'Saturday' /*|| $next->format('l') == 'Sunday'*/ )
				{
					$next->modify( '+1 days' );
				}
			}
		}
		
		return $recurrences;
	}

	/**
	 * Calculates the number of time intervals between two dates.
	 *
	 * @param $start the first date
	 * @param $end the second date
	 * @param $duration a string specifying the recurrence of the format "<amount> <unit>"
	 */
	protected function intervals_between( $start, $end, $duration )
	{
		// obvious for starts after the end date
		if( $start->format('U') > $end->format('U') ) return 0;
		
		RSLog::debug( "Calculating intervals between ".$start->format('Y-m-d').
			" and ".$end->format('Y-m-d'));
		
		// split up the duration
		list( $expr, $unit ) = explode( " ", $duration );

		$count = 0;
/*
		$multiplier = 512;
		$date = clone $start;
		$date->modify( '+'.(( $count + $multiplier) * $expr).' '.$unit );
		while( $date->format('U') < $end->format('U') )
		{
			$count += $multiplier;
			$multiplier /= 2;
			$date->modify( '+'.(( $count + $multiplier ) * $expr).' '.$unit );
		}
		
		RSLog::debug( "Count: $count" );
*/		
		$date1 = clone $start;
		$date2 = clone $end;
		$date1->modify( '+'.( $count * $expr ).' '.$unit );
		$date2->modify( '+'.( $expr ).' '.$unit );
		
		$count += ( $end->format('U') - $date1->format('U') ) / ( $date2->format('U') - $end->format('U') );
		RSLog::debug( $count );
		return $count;
	}
	
	/**
	 * Calculates the number of time intervals between two dates.
	 *
	 * @param $start the first date
	 * @param $end the second date
	 * @param $duration a string specifying the recurrence of the format "<amount> <unit>"
	 */
	public function timeIntervalsBetweenDates( $start, $end, $interval )
	{
		// obvious for starts after the end date
		if( $start->format('U') > $end->format('U') ) return 0;
		
		RSLog::debug( "Calculating intervals between ".$start->format('Y-m-d').
			" and ".$end->format('Y-m-d'));
		
		// split up the duration
		list( $expr, $unit ) = explode( " ", $interval );

		$intDate = (clone $start)->modify( '+'.( $expr ).' '.$unit );		
		$intervals = ( ($end->format('U') - $start->format('U')) / 
			( $intDate->format('U') - $start->format('U') ) );
		
		RSLog::debug( $intervals );
		return $intervals;
	}

}

class RSRecurrenceRule
{
	public	$interval;
	public	$timeUnit;
	public	$numberOfOccurrences;
	public	$daysOfWeek;
	
	public	$skipWeekendDays;
}
?>