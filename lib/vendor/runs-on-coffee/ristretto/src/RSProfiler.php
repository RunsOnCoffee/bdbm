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
declare( ticks = 1 );

/*
Profiling notes

So what do we want to test?
- execution time of a particular part of the script
- time and variability of cycles in a loop
-	start loop
- 	end loop
- measure memory usage at points during execution

- get_included_files
*/


/**************************************************************************//**
 * The RSProfiler facilitates basic profiling of scripts.
 * 
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 *****************************************************************************/
class RSProfiler
{ 
	protected static	$sharedInstance;
	protected static	$tickTime = 0;
	protected static	$samples = array();

	protected	$marks;
	protected	$timedTests;

	public function __construct()
	{
		$this->constructTime = microtime( true );
		$this->marks = array();
		$this->timedTests = array();
	}

	/**************************************************************************//**
	 * Manages the shared instance of RSProfiler.
	 *****************************************************************************/
	public static function sharedInstance()
	{
		if( self::$sharedInstance === null ) self::$sharedInstance = new RSProfiler();
		return self::$sharedInstance;
	}


	/**************************************************************************//**
	 * Sets a time mark during execution.
	 *****************************************************************************/
	public static function mark( $mark )
	{
		$p = RSProfiler::sharedInstance();
		$p->marks[ $mark ][] = microtime( true );
	}
	
	/**************************************************************************//**
	 * Starts or ends a timed execution test. This function should be called twice
	 * with the same string parameter for $mark, specifying the beginning and end
	 * of the execution test.
	 *****************************************************************************/
	public static function clock( $mark )
	{
		$p = RSProfiler::sharedInstance();
		$p->timedTests[ $mark ][] = microtime( true );
	}

	/**************************************************************************//**
	 * Start profiling a block of code.
	 *
	 * Modified from: 
	 * - https://kpayne.me/2013/12/24/write-your-own-code-profiler-in-php/
	 *****************************************************************************/
	public static function startProfiler()
	{
		self::$tickTime = microtime( true );
        $success = register_tick_function(array(__CLASS__, '_sample'));	
	}
	
	public static function stopProfiler()
	{
        unregister_tick_function(array(__CLASS__, '_sample'));
	}
	
	/**************************************************************************//**
	 * Sample the current code for data. Called every tick during profiling, so it
	 * needs to run FAST.
	 *****************************************************************************/
	public static function _sample()
	{
		// sample at beginning -- ignore time taken by our profiling code
		$now = microtime( true );

		// grab stack backtrace
		// keeps the object in case we need to reflect to get name -- costly though
		// limits to 2 stack frames if possible
		if( version_compare( PHP_VERSION, '5.3.6' ) < 0 )
			$bt = debug_backtrace( true );
		else if( version_compare( PHP_VERSION, '5.4.0' ) < 0 )
			$bt = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT );
		else
			$bt = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 2 );

		// pull the frame of the calling function
		$frame = ( count( $bt ) >= 2 ) ? $bt[1] : $bt[0];

		// If the calling function was a lambda, the original file is stored here.
		// Copy this elsewhere before unsetting the backtrace
		$lambda_file = @$bt[0]['file'];
		unset( $bt );

		// Function
		$f = ( isset( $frame['object'] ) ) ? 
			get_class($frame['object']) . '::' . $frame['function'] :
			$frame['function'];

		if( !isset( self::$samples[ $f ] ) ) self::$samples[ $f ] = 0;
		self::$samples[ $f ] += $now - self::$tickTime;
		self::$tickTime = $now;
	}
	
	protected static function sourceFile( $frame )
	{
		$file = "";
	
		// Include/require
		if ( in_array( strtolower( $frame['function'] ), array( 'include', 'require', 'include_once', 'require_once' ) ) ) {
			$file = $frame['args'][0];

		// Object instances
		} elseif ( isset( $frame['object'] ) && method_exists( $frame['object'], $frame['function'] ) ) {
			try {
				$reflector = new ReflectionMethod( $frame['object'], $frame['function'] );
				$file = $reflector->getFileName();
			} catch ( Exception $e ) {
			}

		// Static method calls
		} elseif ( isset( $frame['class'] ) && method_exists( $frame['class'], $frame['function'] ) ) {
			try {
				$reflector = new ReflectionMethod( $frame['class'], $frame['function'] );
				$file = $reflector->getFileName();
			} catch ( Exception $e ) {
			}

		// Functions
		} elseif ( !empty( $frame['function'] ) && function_exists( $frame['function'] ) ) {
			try {
				$reflector = new ReflectionFunction( $frame['function'] );
				$file = $reflector->getFileName();
			} catch ( Exception $e ) {
			}

		// Lambdas / closures
		} elseif ( '__lambda_func' == $frame['function'] || '{closure}' == $frame['function'] ) {
			$file = preg_replace( '/\(\d+\)\s+:\s+runtime-created function/', '', $lambda_file );

		// File info only
		} elseif ( isset( $frame['file'] ) ) {
			$file = $frame['file'];

		// If we get here, we have no idea where the call came from.
		// Assume it originated in the script the user requested.
		} else {
			$file = $_SERVER['SCRIPT_FILENAME'];
		}
		
		return $file;
	}
	
	/**************************************************************************//**
	 * Return profiler data.
	 *****************************************************************************/
	public static function report()
	{
		$p = RSProfiler::sharedInstance();

		global $startTime;
		if( !isset( $startTime ) ) $startTime = $p->constructTime;

		$benchmarks = array();
		$timedTests = array();

		$end = microtime( true );
		$time = $end - $startTime;

		foreach($p->marks as $mark => $times)
		{
			$totalTime = 0.0;
			for($i = 0; $i < count($times); $i++ )
			{					
				// format time -- only allow 2 digits before decimal
//				$timeString = round($times[$i], 4);
//				$timeString = substr($timeString, strpos($timeString, '.') - 2);
				
				// calculate elapsed and total times
				if($i != 0) $elapsedTime = round($times[$i] - $times[ $i - 1 ], 4);
				else $elapsedTime = 0.0;
				$totalTime += $elapsedTime;
				
				// calculate average
				if($i != 0) $averageTime = round(((float)$totalTime / (float)$i), 4);
				else $averageTime = 0.0;
				
				// calculate percentage total
				$percentageTotal = round((($elapsedTime / $time) * 100.0), 1);
				
				$benchmarks[ $mark ] = array(
					'index' => $i + 1,
//					'time'	=> $timeString,
					'elapsed' => $elapsedTime,
					'total' => $totalTime,
					'avg' => $averageTime,
					'pct' => $percentageTotal
				);
			}
		}
		
		foreach($p->timedTests as $mark => $times)
		{
			$runs = array();

			for( $i = 0; $i < count( $times ); $i += 2 )
			{
				$totalTime = round($times[ $i+1 ] - $times[$i], 5);
				$percentageTotal = round((($totalTime / $time) * 100.0), 2);
				
			}
			

			// format time -- only allow 2 digits before decimal
// 			$timeString = round($times[0], 4);
// 			$timeString = substr($timeString, strpos($timeString, '.') - 2);

			$timedTests[ $mark ] = array(
// 				'time'	=>	$timeString,
				'total' =>	$totalTime,
				'pct'	=>	$percentageTotal
			);
		}
		
		return array( 
			'Script Time' =>	$time,
			'Benchmarks' =>		$benchmarks,
			'Timed Tests' =>	$timedTests,
			'Samples'	=>		self::$samples,
		);
	}
}

?>