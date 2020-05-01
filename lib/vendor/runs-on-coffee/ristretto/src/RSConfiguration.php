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
 * Representation of a configuration file. Provides methods for reading from and
 * saving to a .ini file.
 *
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 */
class RSConfiguration
{
	public	$config;

	/**
	 * Creates a new instance of RSConfiguration using the supplied array.
	 *
	 * @param array $array
	 * @return RSConfiguration
	 */
	public static function createWithArray( $array )
	{
		$c = new RSConfiguration;
		$c->config = $array;
		
		return $c;
	}
	
	/**
	 * Creates a new instance of RSConfiguration by reading the configuration
	 * file at $path.
	 *
	 * @param array $path
	 * @return RSConfiguration
	 */
	public static function createWithFile( $path, $sections = true )
	{
		if( !file_exists( $path ) )
			throw new Exception( "File '$path' does not exist." );
		
		$config = parse_ini_file( $path, $sections );
		
		$c = new RSConfiguration;
		$c->config = $config;
		
		return $c;
	}
	
	/**
	 * Save the configuration data to $path, separated by sections if $withSections
	 * is true.
	 *
	 * @param string $path
	 * @param bool $withSections
	 * @return bool
	 */
	public function save( $path, $sections = false )
	{
		$content = $this->arrayAsIniString( $this->array, $sections );

		if ( !$handle = fopen( $path, 'w' ) ) { 
			throw new Exception( "Unable to open '$path' for writing." );
		}

		$success = fwrite( $handle, $content );
		fclose( $handle ); 

		return $success; 
	}

	/**
	 * Accessor function providing direct access to configuration variables.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name )
	{
		if( !isset( $this->config->$name ) ) return null;
		return $this->config->$name;
	}

	/**
	 * Accessor function providing direct access to configuration variables.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function __set( $name, $value )
	{
		$this->config->$name = $value;
	}

	/**
	 * Constructs a string representation of the object's configuration data
	 * in an .ini file format.
	 *
	 * @param string $assoc_arr
	 * @param bool $has_sections
	 * @return string
	 */
	protected function arrayAsIniString( $assoc_arr, $has_sections = false )
	{ 
		$content = ""; 
		if ( $has_sections )
		{ 
			foreach ( $assoc_arr as $key=>$elem )
			{ 
				$content .= "[".$key."]\n"; 
				foreach ( $elem as $key2=>$elem2 )
				{ 
					if( is_array( $elem2 ) ) 
					{ 
						for( $i=0;$i<count( $elem2 );$i++ ) 
						{ 
							$content .= $key2."[] = \"".$elem2[$i]."\"\n"; 
						} 
					} 
					else if( $elem2=="" ) $content .= $key2." = \n"; 
					else $content .= $key2." = \"".$elem2."\"\n"; 
				} 
			} 
		} 
		else
		{ 
			foreach ( $assoc_arr as $key=>$elem )
			{ 
				if( is_array( $elem ) ) 
				{ 
					for( $i=0;$i<count( $elem );$i++ ) 
					{ 
						$content .= $key."[] = \"".$elem[$i]."\"\n"; 
					} 
				} 
				else if( $elem=="" ) $content .= $key." = \n"; 
				else $content .= $key." = \"".$elem."\"\n"; 
			} 
		} 

		return $content;
	}
}
?>