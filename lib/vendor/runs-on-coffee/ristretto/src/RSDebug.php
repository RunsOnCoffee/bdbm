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

/**************************************************************************//**
 * Drop-in debugging library for PHP projects. Needs significant work.
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 *****************************************************************************/

class RSDebug
{
	protected	isEnabled;
	protected	globalsCount;
	protected	debugLog;

	/**************************************************************************//**
	 * Manages the shared instance of RSDebug. This is a global log structure that
	 * can be used throughout an application for logging facilities.
	 *****************************************************************************/
	public static function sharedInstance()
	{
		if( self::$sharedInstance === null ) self::$sharedInstance = new RSLog();
		return self::$sharedInstance;
	}

	public static function enabled()
	{
		$debug = RSDebug::sharedInstance();
		return ( $debug->isEnabled ) ? true : false;
	}
	
	public static function log( $msg )
	{
		$debug = RSDebug::sharedInstance();
		if( !$debug->isEnabled ) return;

		// log as a debug message
		RSLog::debug( $msg );

		// store for printing later
		$time = date("Y-m-d h:i:s");
		list($usec, ) = explode(" ", microtime());
		$usec = round($usec, 4);
		$usec = substr("$usec", 2, 10);
		
		// pad the time string with trailing zeroes
		$timeString = "$time.$usec";
		for($i = strlen($timeString); $i < 24; $i++) $timeString .= '0';
		
		$debug->debugLog[] = array(
				'time' =>		$timeString,	
				'msg' =>		$text
			);
	}

	public function __construct()
	{
		$this->debugLog = array();
	
		// store initial count of globals -- ignored
		// after $debug_log, so that log is ignored
		@$this->globalsCount = count( $GLOBALS );

		// print debug log on end
//		if($CONFIG['debug_enabled']) register_shutdown_function("debug_report");

	}



	public function printStyleSheet()
	{
		print '
			<style type="text/css">
			#debug { background-color: #fff; border: 1px dotted black; padding: 2px; font-size: 10px;
						margin: 10px; font-family: Verdana, Arial, Helvetica, Geneva, sans-serif; margin-top: 800px; }

			#debug h2 { margin-top: 0px; margin-bottom: 10px; padding: 2px; background: #dddddd; }

			#debug #section { margin: 15px; padding: 4px; }
			#debug #section td { font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 8pt; text-align: left; white-space: normal;}
			#debug #section td.time { text-align: right; width: 100px; white-space: nowrap; }

			#debug #section.globals { background-color: #ffffff; }

			#debug #array {  }
			#debug #array td { background-color: #ddddee; font-family: Verdana, Arial, Helvetica, Geneva, sans-serif; 
						white-space: normal; font-size: 8pt; }
			#debug #array td.key { width: 100px; color: white; font-weight: bold; text-align: center; white-space: nowrap; }

			/* types */
			#debug #array td.integer { color: green; }
			#debug #array td.double { color: blue; }
			#debug #array td.boolean { color: #d90081; }
			#debug #array td.NULL { color: darkorange; }
			
			</style>
		';
	}
	

	// debug_print_array
	// @desc Formats an array into a nested table structure for display.
	protected function printArray( $array, $step = 0 )
	{	
		$key_bg_color = '1e32c8';
		$output = '';
			
		// lighten up the background color for the key cells
		if( $step > 0 )
		{
			for($i = 0; $i < 6; $i += 2)
			{
				$c = substr( $key_bg_color, $i, 2 );
				$c = hexdec( $c );
				
				// step color up a bit
				if(( $c += $step * 15 ) > 255) $c = 255;
				
				isset($tmp_key_bg_color) or $tmp_key_bg_color = '';
				$tmp_key_bg_color .= sprintf( "%02X", $c );
			}
			$key_bg_color = $tmp_key_bg_color;
		}
		
		// begin table
		$output .= '<table id="array" width="100%" cellspacing="1">';
		//$only_numeric_keys = ($this->_only_numeric_keys( $array ) || count( $array ) > 50);

		foreach( $array as $key => $value )
		{			
			$value_style = '';
			$key_style = '';
			
			$type = gettype( $value );
			
			// change the color and format of the value and set the values title
			$type_title = $type;
			$type_class = $type;				// used as CSS style
			
			switch( $type )
			{
				case 'array':
					if( empty( $value ) )
					{
						$type_title = 'empty array';
						$value_text = "[]";
						$value_style = 'color: darkorange;';
					}
					else
					{
						// recurse and print
						$value_text = $this->printArray($value, $step + 1);
					}
					break;
				
				case 'object':
					$key_style = 'color: #ff9b2f;';
					$value_text = "object";
					
					// perhaps display object variables??
					/*
						if( $this->show_object_vars ) {
							$this->print_a( get_object_vars( $value ), TRUE, $key_bg_color );		
					*/
					break;
				
				case 'boolean':
					$value_text = ($value == true) ? "true" : "false";
					break;
					
				case 'NULL':
					$value_text = "NULL";
					break;
				
				case 'string':
					if( $value == '' )
					{
						$value_style = 'color: darkorange;';
						$value = "''";
						$type_title = 'empty string';	
					}
					else
					{
						$value_style = 'color: black;';
						$value = htmlspecialchars( $value );
					
						$value = utilities_format_whitespace( $value );
						$value = nl2br($value);
						
						// use different color for string background
						//if(strstr($value, "\n")) $value_style_content = 'background:#ecedfe;';
					}
					
					$value_text = $value;
					break;
				
				case 'integer':
				case 'double':
				default:
					$value_text = $value;
			}

			$output .= '<tr>';
			$output .= '<td class="key" style="background-color: #'.$key_bg_color. ';';
			if($key_style != "") $output .= $key_style;
			$output .= '" title="Key: '.gettype( $key ).', Value: '.$type_title.'">';
			$output .= utilities_format_whitespace( $key );
			$output .= '</td>';
			$output .= '<td class="'.$type_class.'" style="'.$value_style.'">';
			$output .= $value_text;			
			$output .= '</td>';
			$output .= '</tr>';
		}
		
	/*
		// limiting code
		$entry_count = count( $array );
		$skipped_count = $entry_count - $this->limit;

		if( $only_numeric_keys && $this->limit && count($array) > $this->limit)
		{
			$this->output .= '<tr title="showing '.$this->limit.' of '.$entry_count.' entries in this array"><td style="text-align:right;color:darkgray;background-color:#'.$key_bg_color.';font:bold '.$this->fontsize.' '.$this->fontfamily.';">...</td><td style="background-color:#'.$this->value_bg_color.';font:'.$this->fontsize.' '.$this->fontfamily.';color:darkgray;">['.$skipped_count.' skipped]</td></tr>';
		}
	*/
		
		// close table and return
		$output .= '</table>';
		return $output;
	}


	protected function format_log()
	{
		$output = '<div id="section"><h3>Debug Log:</h3><table width="100%" cellspacing="0" cellpadding="3">';
		foreach( $this->debugLog as $e )
		{
			$output .= '<tr><td class="time">'.$e['time'].':</td><td>'.$e['msg'].'</td></tr>';
		}
		$output .= "</table></div>";
		
		return $output;
	}

	public function report()
	{
		// track statistics for debug report
		$startTime = microtime();

		// pull in globals needed
		global $GLOBALS;
		global $CONFIG;
		global $globals_count;
		
		$output = "";

		// produce some CSS data
		if($CONFIG['debug_style']) $this->printStyleSheet();
	
		// print debugging log
		$output .= $this->format_log();

		// collect script globals
		$varcount = 0;
		foreach($GLOBALS as $g_key => $g_value)
		{
			if(++$varcount > $globals_count)
			{
				// these are handled later so don't process them
				if ($g_key != 'HTTP_SESSION_VARS' && $g_key != '_SESSION')
				{
					$script_globals[$g_key] = $g_value;
				}
			}
		}
		
		// no need to show our own global
		unset($script_globals['globals_count']);

		// print variable arrays
		$variablesArray['script_globals'] = array('Script Globals', '#7DA7D9', 'globals');
		$variablesArray['_GET'] = array('$_GET', '#7DA7D9', '_get');
		$variablesArray['_POST'] = array('$_POST', '#F49AC1', '_post');
		$variablesArray['_FILES'] = array('$_FILES', '#82CA9C', '_files');
		$variablesArray['_SESSION'] = array('$_SESSION', '#FCDB26', '_session');
		$variablesArray['_COOKIE'] = array('$_COOKIE', '#A67C52', '_cookie');
		
		if($CONFIG['debug_server_vars']) $variablesArray['_SERVER'] = array('$_SERVER', '#f3f4f5', '_server');
		if($CONFIG['debug_env_vars']) $variablesArray['_ENV'] = array('$_ENV', '#f3f4f5', '_env');

		foreach($variablesArray as $arrayName => $arrayData)
		{
			if($arrayName != 'script_globals') global $$arrayName;
			if($$arrayName)
			{
				$output .= '<div id="section" class="'.$arrayData[2].'"><h3>'.$arrayData[0].'</h3>';
				$output .= debug_print_array( $$arrayName );
				$output .= '</div>';
			}
		}

		$endTime = microtime();
		
		list($s_usec, $s_sec) = explode(" ", $startTime);
		list($e_usec, $e_sec) = explode(" ", $endTime);
		$runTime = round(((float)$e_usec + (float)$e_sec) - ((float)$s_usec + (float)$s_sec), 6);

		// output report
		$output = '<div id="debug">
			<h2>DEBUG
				<span style="color: red; font-weight: normal; font-size: 9px; float: right; text-align: right; margin-top: 4px;">
					(debug generated in <strong>'.$runTime.' s</strong>)
				</span>
			</h2>
		'.$output.'
		</div>';

		//	Debug Window
		//	Currently not functioning
/*		
		if($CONFIG['debug_window'])
		{
			$debugwindow_origin = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
			print '
				<script type="text/javascript" language="JavaScript">
					var debugwindow;
					alert("Debugging...");	
					debugwindow = window.open("", "", "menubar=no,scrollbars=yes,resizable=yes,width=640,height=480");
					debugwindow.document.open();
					debugwindow.document.write("'.addslashes($output).'");
					debugwindow.document.close();
					debugwindow.document.title = "Debugwindow for : http://'.$debugwindow_origin.'";
					debugwindow.focus();
				</script>
			';
		}
		else
		{
*/
			print $output;

/*
		}
*/
	}

	protected function htmlWhitespace( $string )
	{
		//$string = str_replace(' ', '&nbsp;', $string);
		$string = preg_replace(array('/&nbsp;$/', '/^&nbsp;/'), '<span style="color:red;">_</span>', $string); /* replace spaces at the start/end of the STRING with red underscores */

		// insert normal spaces for breaking
		//$string = str_replace('&nbsp;', ' &nbsp;', $string);

		//$string = preg_replace('/\t/', '&nbsp;&nbsp;<span style="border-bottom: #000 solid 1px;">&nbsp;</span>', $string); /* replace tabulators with '_ _' */
		$string = preg_replace('/\t/', '&nbsp;&nbsp;&nbsp;&nbsp;', $string); /* replace tabulators with '_ _' */
		return $string;
	}
}
?>