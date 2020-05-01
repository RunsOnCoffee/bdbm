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
 * The RSTemplate class provides a facility for creating and manipulating dynamic
 * content quickly and efficiently.
 * 
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 *****************************************************************************/
class RSTemplate
{ 
    protected	$parameters; 		///< values to be inserted in the template 
    protected	$path; 				///< path to the templates 

	/**************************************************************************//**
	 * Constructor 
	 * 
	 * @param string $path the path to the templates  
	 *****************************************************************************/
	public function __construct( $path = null )
	{ 
		$this->path = $path; 
		$this->parameters = array(); 
	} 

	/**************************************************************************//**
	 * Overloaded set operator.
	 *****************************************************************************/
	public function __set( $name, $value )
	{
    	$this->parameters[ $name ] = $value;
	}

    /**************************************************************************//**
	 * Set the path to the template files. 
	 * @param string $path path to template files 
     *****************************************************************************/
    public function setPath( $path )
    { 
		$this->path = $path; 
	} 

    /**************************************************************************//**
	 * Set a template variable. 
	 * 
	 * @param string $name name of the variable to set 
	 * @param mixed $value the value of the variable 
     *****************************************************************************/
    function set($name, $value)
    { 
    	$this->parameters[ $name ] = $value; 
    } 

    /**************************************************************************//**
	 * Set a bunch of variables at once using an associative array. 
	 * 
	 * @param array $parameters array of parameters to set 
	 * @param bool $clear whether to completely overwrite the existing parameters  
     *****************************************************************************/
    public function setParameters( $parameters, $clear = false )
    { 
		if( $clear ) $this->parameters = $parameters; 
		else if(is_array($parameters)) $this->parameters = array_merge( $this->parameters, $parameters );  
	} 

    /**************************************************************************//**
	 * Open, parse, and return the template file. 
	 * 
	 * @param string string the template file name  
	 * @return string 
     *****************************************************************************/
    public function fetch( $file )
    {
    	$path = $file;
    	if( !empty( $this->path ) ) $path = $this->path . $file;
    	if( !file_exists( $path ) ) throw new \Exception( "File '$path' does not exist." );
    
    	// turn off notices in error reporting; this should be an OPTION
		$err = error_reporting();
		error_reporting( E_ALL ^ E_NOTICE );

		// extract the parameters to local namespace
		extract($this->parameters);
		
		// process the template file
		ob_start();
		include $path;
		$contents = ob_get_contents();
		ob_end_clean();
		
		// return error reporting to normal
		error_reporting( $err );
		
		return $contents;
	} 

	/**************************************************************************//**
	 * Open, parse, and return the template string. Added to allow templates stored in
	 * the database to be retrieved and processed.
	 *
	 * @author Nicholas Costa
	 * @param $t a string template to be evaluated and processed
	 * @return string
	 *****************************************************************************/
	public function fill( $t )
	{
		// prepare the template to be eval'd
		$t = str_replace('<'.'?php', '<'.'?', $t);
		$t = '?'.'>'.$t.'<'.'?';
		
    	// turn off notices in error reporting; this should be an OPTION
		$err = error_reporting();
		error_reporting( E_ALL ^ E_NOTICE );

		// extract the parameters to local namespace
		extract($this->parameters);
		
		// process the template contents in memory
		ob_start();
		eval( $t );
		$contents = ob_get_contents();
		ob_end_clean();
		
		// return error reporting to normal
		error_reporting( $err );

		return $contents;
	}
} 
?>