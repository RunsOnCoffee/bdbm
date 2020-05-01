<?php
/*
 * Ristretto - a general purpose PHP object library
 * Copyright (C) 2017 Nicholas Costa. All rights reserved.
 * 
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
 * Base class for shell commands.
 *
 * @todo Decouple from needing to extend class -- make it take a user object
 * @todo Upgrade to use docopt.
 * @todo Implement a system to uninstall a shell command, including related files. 
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 */
class RSShellCommand
{
	protected static	$sharedInstance;

	protected	$command;
	protected	$config;
	protected	$optionString;
	protected	$longOptions;
	protected	$arguments;
	protected	$actions;

	/**
	 * Manages the shared instance of RSShellCommand. This is a global structure that
	 * can be used throughout an application.
	 */
	public static function sharedInstance()
	{
		if( self::$sharedInstance === null ) self::$sharedInstance = new RSShellCommand();
		return self::$sharedInstance;
	}

	public function __construct()
	{
		$this->actions = array();
		$this->arguments = array();
		$this->optionString = "";
		$this->longOptions = array();
//		$this->configFileLocations = $this->configurationFiles();

		// find actions
		$className = get_class( $this );
		RSLog::debug( $className );

		$class = new \ReflectionClass( $className );
		$methods = $class->getMethods();
		foreach( $methods as $m )
		{
			if( substr( $m->name, -6 ) == 'Action' )
			{
				$verb = substr( $m->name, 0, -6 );
				$this->actions[ $verb ] = $m; 
			}
		}
	}
	
	/**
	 * Returns a list of configuration file locations. Subclasses can override this
	 * to return alternative locations for configuration.
	 */
	protected function configurationFiles()
	{
		$os = $this->detectOS();
		$files = array();
		$confName = $this->identifier().".conf";
		switch( $os )
		{
			case "Darwin":
				$files[] = '/etc/'.$confName;
				$files[] = '/Library/Preferences/'.$confName;
				$files[] = RSPath::expandPath( '~/Library/Preferences/'.$confName );
				break;

			case "Linux":
				$files[] = '/etc/'.$confName;
				$files[] = RSPath::expandPath( '~/.'.$confName );
				break;
		}
		
		return $files;
	}
	
	/*
	 * Returns the identifier string for this command. Default action is to return
	 * the command name used on the command line. Subclasses can override this
	 * function to return a more discrete identifier to be used for logs and
	 * configuration files.
	 *
	 * @return string The identifier string for the shell command
	 */
	public function identifier()
	{
		global $argv;

		RSLog::debug( "Identifier: ".basename( $argv[0] ) );
		return basename( $argv[0] );
	}
		
	/**
	 * 
	 */
	protected function initialize()
	{
		global $argc, $argv;
		
		$config = array();
		$files = $this->configurationFiles();
		foreach( $files as $path )
		{
			RSLog::debug( "Checking for '$path'" );
			if( file_exists( $path ) )
			{			
				RSLog::debug( "File '$path' found" );
				$fileConfig = parse_ini_file( $path );
				$config = array_merge( $config, $fileConfig );
			}
		}

		$this->config = $config;

		// analyze command line parameters
		$this->command = $argv[0];
		$options = RSArguments::getopt( $this->optionString, $this->longOptions );
		$this->config = array_merge( $this->config, $options );
				
		// parse out arguments
		$i = 1;
		while( $i < $argc )
		{
			if( $argv[ $i ][0] == '-' )
			{
				$opt = $argv[$i][1];
				if( $options[ $opt ] !== true ) $i += 2;
				else $i++;
				continue;
			}
			
			$this->arguments[] = $argv[ $i ];
			$i++;
		}
	}

	/**
	 * Performs configuration needed after initialization from known configuration
	 * files and command line options. Abstract; subclasses can implement this to
	 * add more specific configuration.
	 */
	protected function configure()
	{
	}

	/**
	 *
	 */
	public function main()
	{
		// initialize from basic configuration
		$this->initialize();

		// handle any additional configuration needed
		$this->configure();

		// check if first argument is an implemented action
		RSLog::debug( "First argument: ".$this->arguments[0] );
		RSLog::debug( $this->actions );
		
		if( isset( $this->arguments[0] ) &&
			isset( $this->actions[ $this->arguments[0] ] ) )
		{
			$method = $this->actions[ $this->arguments[0] ]->name;
			RSLog::debug( "Method: $method" );
			$this->$method();
		}
		else
		{
			$this->run();
			exit();	
		}				
	}
	
	/**
	 *
	 */
	public function run()
	{
		$this->usage();
	}
	
	/**
	 * Prints a generic help message detailing usage of the shell command. Should
	 * be overridden by subclasses to produce a more helpful message.
	 */
	public function usage()
	{
		global $argv;
		$script = $argv[0];
		$usage = 
"Usage: $script command <options>
       $script command
";
		echo $usage;
	}
	
	/**
	 * Quick helper function to detect OS.
	 */
	protected function detectOS()
	{
		$os = php_uname();
		if( strpos( $os, 'Darwin' ) !== false ) $os = 'Darwin';
		else if( strpos( $os, 'Linux' ) !== false ) $os = 'Linux';
		
		RSLog::debug( $os );
		return $os;
	}

}
?>