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

/**
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 */

class RSArguments
{
	public static function getopt( $options, $longopts = null )
	{
		global $argv;

		// prep the possible option combinations
		$ops = array();
		preg_match_all( '/[A-Za-z]:{0,2}/', $options, $matches );
		
		$optionTypes = ( $longopts ) ? array_merge( $matches[0], $longopts ) : $matches[0];
		foreach( $optionTypes as $k )
		{
			$p = trim( $k, ':' );
			if( strstr( $k, '::' ) ) $ops[ $p ] = 'optional';
			else if( strstr( $k, ':' ) ) $ops[ $p ] = 'required';
			else $ops[ $p ] = 'no value';
		}
		
		// process each argument
		$params = array();
		$args = count( $argv );
		for( $i = 1; $i < $args; $i++ )
		{
//			echo "Testing ".$argv[$i]."\n";
			if( $argv[ $i ][0] != '-' ) continue;
			
			// match for long option with or without parameter
			if( preg_match( '/--([^= ]+)=?(.+)?/', $argv[ $i ], $match ) )
			{
//				print_r( $match );
				if( !isset( $ops[ $match[1] ] ) ) continue;

				if( $ops[ $match[1] ] == 'optional' || $ops[ $match[1] ] == 'required' )
				{
					if( isset( $match[2] ) )
					{
						$params[ $match[1] ] = $match[2];
					}
					else if( isset( $argv[$i+1] ) && $argv[$i+1][0] != '-' )
					{
						$params[ $match[1] ] = $argv[$i+1];
						$i++;
					}
					else if( $ops[ $match[1] ] == 'optional' )
					{
						$params[ $match[1] ] = true;
					}
				}
				else
				{
					// set option as true and ignore any parameters
					$params[ $match[1] ] = true;
				}
			}
			
			// match for short option with parameter
			if( preg_match( '/-([A-Za-z])=(.+)/', $argv[ $i ], $match ) )
			{
//				print_r( $match );
				if( !isset( $ops[ $match[1] ] ) ) continue;

				if( $ops[ $match[1] ] == 'optional' || $ops[ $match[1] ] == 'required' )
				{
					if( isset( $match[2] ) )
					{
						$params[ $match[1] ] = $match[2];
					}
					else if( isset( $argv[$i+1] ) && $argv[$i+1][0] != '-' )
					{
						$params[ $match[1] ] = $argv[$i+1];
						$i++;
					}
					else if( $ops[ $match[1] ] == 'optional' )
					{
						$params[ $match[1] ] = true;
					}
				}
				else
				{
					// set option as true and ignore any parameters
					$params[ $match[1] ] = true;
				}
			}
			// match for short option without parameter
			else if( preg_match( '/-([A-Za-z]+)/', $argv[ $i ], $match ) )
			{
//				print_r( $match );
				for( $c = 0; $c < strlen( $match[1] ); $c++ )
				{
					$char = $match[1][$c];
					
					if( !isset( $ops[ $char ] ) ) continue;

					if( $ops[ $char ] == 'optional' || $ops[ $char ] == 'required' )
					{
						if( isset( $match[2] ) )
						{
							$params[ $char ] = $match[2];
						}
						else if( isset( $argv[$i+1] ) && $argv[$i+1][0] != '-' )
						{
							$params[ $char ] = $argv[$i+1];
							$i++;
						}
						else if( $ops[ $char ] == 'optional' )
						{
							$params[ $char ] = true;
						}
					}
					else
					{
						// set option as true and ignore any parameters
						$params[ $char ] = true;
					}
				}
			}
			
		}
		
		return $params;
	}

}

?>