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

/*! \mainpage RSArray Documentation
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
 * Utility class for useful array routines.
 *
 * @todo Add color support to textTable().
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 */
class RSArray
{
	/**
	 * Constructs an HTML table from the supplied array.
	 *
	 * @param array $array
	 * @return RSConfiguration
	 */
	public static function htmlTable( $array )
	{
		$html = "<table class='hci-table'>";
		foreach( $array as $row )
		{
			$html .= '<tr>';
			foreach( $row as $cell ) $html .= "<td>{$cell}</td>";
			$html .= '</tr>';
		}
		
		return $html.'</table>';
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
		$defaults = array(
				'header'  => true,
				'corner-char'  => "+",
				'row-char'  => "-",
				'col-char'  => "|",
				'padding-left' => 2,
				'padding-right' => 2,
				'max-width' => 150,
				'max-col-width' => 30,
				'return' => false,
			);

		$defaults['max-width'] = exec('tput cols');
		
		// configuration array
		$c = ( is_array( $options ) ) ? array_merge( $defaults, $options ) : $defaults;
		
		$colWidths = array();
		
		for( $i = 0; $i < count( $array ); $i++ )
		{
			$row = array_values( $array[ $i ] );

			for( $j = 0; $j < count( $row ); $j++ )
			{
				$calcW = strlen( $row[ $j ] )
					+ $c['padding-left']
					+ $c['padding-right'];
				
				// handle wrapping
				if( $calcW >= $c['max-col-width']) $calcW = $c['max-col-width'];
									
				if( !isset( $colWidths[ $j ] ) || $colWidths[ $j ] < $calcW ) 
					$colWidths[ $j ] = $calcW;
			}
		}
		
		$width = array_sum( $colWidths ) + ( count( $colWidths ) ) + 1;
// print_r( $colWidths );
// echo "Width: $width\n";

		// handle line width greater than max-width
		$delta = $width - $c['max-width'];
		if( $delta > 0 )
		{
			for( $j = 0; $j < count( $colWidths ); $j++)
			{
				$colWDelta = round( ( $colWidths[ $j ] / $width ) * $delta );
				$colNWidth = $colWidths[ $j ] - $colWDelta;
				
				if( $colNWidth < (1 + $c['padding-left'] + $c['padding-right']  ) ) throw new \Exception( "Column width too small" );
				
				$colWidths[ $j ] = $colNWidth;
			}


			$width = array_sum( $colWidths ) + ( count( $colWidths ) ) + 1;
		}

// print_r( $colWidths );

		
		// buffering
		ob_start();
		
		echo $c['corner-char']
				.str_repeat( $c['row-char'], $width - 2 )
				.$c['corner-char']."\n";
		for( $i = 0; $i < count( $array ); $i++ )
		{
			$r = array_values( $array[ $i ] );

			$numLines = 1;			
			$lines = 0;
			while( $lines < $numLines )
			{
				for( $j = 0; $j < count( $r ); $j++ )
				{
					$data = $r[ $j ];

					$textWidth = $colWidths[ $j ]
						- $c['padding-left']
						- $c['padding-right'];
					$split = wordwrap( $data, $textWidth, "\n", true );
					
					$cellData = explode( "\n", $split );
					$numLines = count( $cellData );
					$text = isset( $cellData[ $lines ] ) ? $cellData[ $lines ] : '';
			
					echo $c['col-char']
						.str_repeat( " ", $c['padding-left'] )
						.str_pad( $text, $colWidths[ $j ] - $c['padding-left'] - $c['padding-right'] )
						.str_repeat( " ", $c['padding-right'] );
				}
				echo "|\n";
				
				$lines++;
			}
			
			// add underline for header
			if( $i == 0 && $c['header'] == true )
				echo $c['col-char']
				.str_repeat( $c['row-char'], $width - 2 )
				.$c['corner-char']."\n";

		}
		echo $c['corner-char']
				.str_repeat( $c['row-char'], $width - 2 )
				.$c['corner-char']."\n";
	
		$text = ob_get_contents();
		ob_end_clean();

		// return text table if desired
		if( $c['return'] == true ) return $text;
		
		// otherwise echo
		echo $text;
	}
}
?>