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

/**
 * The RSURL object is a representation of a uniform resource locator.
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 *
 * @todo Update for use with Ristretto
 */
class RSURL
{
	protected	$url;

	protected	$scheme;
	protected	$host;
	protected	$port;
	protected	$user;
	protected	$pass;
	protected	$path;
	protected	$anchor;
	protected	$params = array();
	protected	$rewriteRules = array();
	
	protected	$modified = false;

	/**
	 * This method returns an instance of the SMURL class representing the URL requested by the
	 * client for this request.
	 *
	 * In order to construct the requested URL, this method attempts to use the server variable
	 * REQUEST_URI. If this is not set, it fails over to SCRIPT_NAME and QUERY_STRING. However,
	 * these do not properly handle rewritten URLs, so issues may arise in this context.
	 *
	 * @return SMURL
	 */
	public static function requestedURL()
	{
		$url = new SMURL;
		
		// set scheme
		$scheme = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http';
		$url->setScheme( $scheme );
		
		// set host
		$url->setHost( $_SERVER['HTTP_HOST'] );
		
		// set path and query
		if( isset( $_SERVER['REQUEST_URI'] ) )
		{
			preg_match( '/\/([^\?]*)(?:\?(.*))?$/', $_SERVER['REQUEST_URI'], $match );
			$url->setPath( $match[1] );
			$url->setQueryString( $match[2] );
		}
		else
		{
			$url->setPath( $_SERVER['SCRIPT_NAME'] );
			$url->setQueryString( $_SERVER['QUERY_STRING'] );
		}

		// set port
		$url->setPort( $_SERVER['SERVER_PORT'] );
		
		return $url;
	}

	/**
	 * Creates a new instance of SMURL. If called with a parameter, the method will attempt to parse
	 * the parameter as a string URL and throw an Exception if it fails.
	 *
	 * @param string $url
	 * @throws Exception
	 */
	public function __construct( $url = null )
	{
		if( !empty( $url ) )
		{
			$this->url = $url;

			if( ($components = parse_url( $url )) === false )
			{
				throw new Exception( "Invalid URL '$url'" );
			}
			
			$this->scheme = $components['scheme'];
			$this->host = $components['host'];
			$this->port = $components['port'];
			$this->user = $components['user'];
			$this->pass = $components['pass'];
			$this->path = ltrim( $components['path'], '/' );
			$this->anchor = $components['fragment'];
			
			// combine 
			if( !empty( $components['query'] )) parse_str( $components['query'], $this->params );
		}
	}
	
	/**
	 * Returns the URL.
	 * @return string
	 */
	public function __tostring()
	{
		return $this->url();
	}
	
	/** 
	 * Returns the URL parameter in the query string named $name. If the parameter is not found, the
	 * method returns null.
	 *
	 * @param string $name
	 * @return string
	 */
	public function get( $name )
	{
		if( isset( $this->params[ $name ] ) ) return $this->params[ $name ];
		return null;
	}
	
	/**
	 * Sets the URL parameter named $name to the value $value in the query string.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function set( $name, $value )
	{
		$this->params[ $name ] = $value;
		if( $value === null ) unset( $this->params[ $name ] );
		
		$this->modified = true;
	}
	
	/**
	 * Sets the URL scheme.
	 * @param string $scheme
	 */
	public function setScheme( $scheme )
	{
		$this->scheme = $scheme;
		$this->modified = true;
	}
	
	/**
	 * Sets the URL host.
	 * @param string $host
	 */
	public function setHost( $host )
	{
		$this->host = $host;
		$this->modified = true;
	}
	
	/**
	 * Sets the URL port.
	 * @param string $port
	 */
	public function setPort( $port )
	{
		$this->port = $port;
		$this->modified = true;
	}
	
	/**
	 * Sets the URL path.
	 * @param string $path
	 */
	public function setPath( $path )
	{
		$this->path = $path;
		$this->modified = true;
	}
	
	/**
	 * Sets the URL query string.
	 * @param string $query
	 */
	public function setQueryString( $query )
	{
		$this->params = array();
		parse_str( $query, $this->params );
		$this->modified = true;
	}
	
	/**
	 * Return the URL string formatted for use on a web page. This will convert HTML entities to
	 * their proper codes before returning the string.
	 *
	 * @return string
	 */
	public function url()
	{
		if( $this->modified === false ) return $this->url;
		else
		{
			$url = $this->generateString();
			return htmlentities( $url );
		}
	}

	/**
	 * Return the URL string in its raw format.
	 * @return string
	 */
	public function raw()
	{
		if( $this->modified === false ) return $this->url;
		else return $this->generateString();
	}

	/**
	 * Generate the URL from the stored components.
	 * @return string
	 */
	protected function generateString()
	{
		// start with the scheme
		$url  = $this->scheme . '://';
		
		// user and password
		if( !empty( $this->user ) )
		{
			$url .= $this->user;
			if( !empty( $this->pass ) ) $url .= ':'.$this->pass.'@';
			else $url .= '@';
		}
		
		// add the host and path
		$url .= $this->host . '/';
		$url .= $this->path;
		
		// add the URL-encoded parameters
		if( !empty( $this->params ) )
		{
			$url .= '?';
			foreach( $this->params as $f => $v ) $url .= $f . '=' . urlencode( $v ) . '&';
			$url = rtrim( $url, '&' );
		}
		
		// add the anchor if any
		if( !empty( $this->anchor ) ) $url .= '#' . $this->anchor;
		
		$this->url = $url;
		$this->modified = false;
		return $url;
	}
}
?>