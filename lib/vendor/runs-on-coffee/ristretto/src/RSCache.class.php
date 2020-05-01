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
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 *
 * @todo Update for use in Ristretto.
 */
class RSCache
{
	protected static $defaultCache;
	
	protected	$cacheDirectory;
	protected	$expireTime;
	protected	$encryptionKey;

	public static function defaultCache()
	{
		if( self::$defaultCache !== null ) return self::$defaultCache;
		
		$cacheDir = '/tmp';
		if( SMApplication::exists() )
		{
			// get application cache directory
		}
		
		self::$defaultCache = new SMCache( $cacheDir );
		return self::$defaultCache;
	}

	public function __construct( $dir = '/tmp', $exp = 300 )
	{
		$this->cacheDirectory = $dir;
		$this->expireTime = $exp;
	}

	/**
	 * Set the time-to-live for cached data accessed through this cache.
	 * @param int $exp
	 */
	public function setExpireTime( $exp = 300 )
	{
		$this->expireTime = $exp;
	}

	/**
	 * Set the key to be used to encrypt and decrypt cached data.
	 * @param int $key
	 */
	public function setEncryptionKey( $key = 300 )
	{
		$this->encryptionKey = $key;
	}

	/**
	 * Cache the given data using the specified cache ID.
	 *
	 * @param string $id
	 * @param mixed $data
	 */
	public function cache( $id, $data )
	{
		$file = $this->cacheFileForID( $id );
		
		// serialize the data to handle objects
		$rep = serialize( $data );
		
		// encrypt if necessary
		if( isset( $this->encryptionKey ) ) $rep = $this->encrypt( $rep );
		
		$f = fopen( $file, "w" );
		fwrite( $f, $rep );
		fclose( $f );
		
		RSLog::debug( "Wrote data for '$id' to cache." );
	}
	
	/**
	 * Retrieve the data cached using the specified cache ID.
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function fetch( $id, $expiry = null )
	{
		$file = $this->cacheFileForID( $id );
		if( $expiry === null ) $expiry = $this->expireTime;
		
		RSLog::debug( "Checking for cache file '$file' (ID '$id') with expiry $expiry" );
		
		// return null if cache file does not exist
		if( !file_exists( $file ) ) return null;
		
		// remove file and return null if cache is expired
		if( filemtime( $file ) + $expiry < time() )
		{
			unlink( $file );
			return null;
		}
		
		$f = fopen( $file, "rb" );
		$data = fread( $f, filesize( $file ) );
		fclose( $f );
		
		// decrypt if necessary
		if( isset( $this->encryptionKey ) ) $data = $this->decrypt( $data );

		// unserialize the data and check its validity
		$rep = @unserialize( $data );
		if( $rep === false )
		{
			RSLog::debug( "Data could not be unserialized." );
			RSLog::debug( $data );
			return null;
		}

		RSLog::debug( "Cache file '$file' (ID '$id') found!" );
		return $rep;
	}
	
	/**
	 * Retrieve the data cached using the specified cache ID. Unlike fetch(), get() does not
	 * test the validity of the cache.
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function get( $id )
	{
		$file = $this->cacheFileForID( $id );
		
		// return null if cache file does not exist
		if( !file_exists( $file ) ) return null;
		
		$f = fopen( $file, "rb" );
		$data = fread( $f, filesize( $file ) );
		fclose( $f );
		
		// decrypt if necessary
		if( isset( $this->encryptionKey ) ) $data = $this->decrypt( $data );

		// unserialize the data and check its validity
		$rep = @unserialize( $data );
		if( $rep === false )
		{
			RSLog::debug( "Data could not be unserialized." );
			RSLog::debug( $data );
			return null;
		}
		
		return $rep;
	}
	
	/**
	 * Cleans out all of the files in the cache directory.
	 * @todo Should make this recursive, so it can handle subdirectories.
	 */
	public function clean()
	{
		$dir = new DirectoryIterator( $this->cacheDirectory );
		foreach( $dir as $d )
		{
			if( !$d->isDot() && !$d->isDir() )
			{
				RSLog::debug( $d->getPathname() );
				unlink( $d->getPathname() );				
			}
		}
	}
	
	/**
	 * Removes the data cached using the specified cache ID.
	 * @param string $id
	 */
	public function invalidate( $id )
	{
		$file = $this->cacheFileForID( $id );
		if( file_exists( $file ) ) unlink( $file );
	}
	
	/**
	 * Reset the modification time of the cache with the specified cache ID so that it continues
	 * to exist in the cache.
	 *
	 * @param string $id
	 */
	public function touch( $id )
	{
		$file = $this->cacheFileForID( $id );
		
		if( !file_exists( $file ) ) throw new Exception( "Invalid cache ID '$id'" );
		touch( $file );
	}
	
	/**
	 * Determines the file name for the specified cache ID.
	 *
	 * @param string $id
	 * @return string
	 */
	protected function cacheFileForID( $id )
	{
		$file  = $this->cacheDirectory . '/';
		$file .= md5( $id );
		return $file;
	}

	/**
	 * Encrypts the encrypted data passed in $data using the stored $encryptionKey. The algorithm used
	 * is AES-256, formerly known as Rijndael, and is supported by the Mcrypt library. The key
	 * for this must be less than or equal to 256 bits, or 32 characters.
	 * 
	 * @param string $data
	 * @return mixed
	 */
	protected function encrypt( $data )
	{
		// generate initialization vector for AES
		$size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
		$iv = mcrypt_create_iv( $size, MCRYPT_RAND );
	
		// encrypt with AES-256 (Rijndael)
		$rep = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $this->encryptionKey, $data, MCRYPT_MODE_ECB, $iv );
		
		// return the encrypted data
		return $rep;
	}
	
	/**
	 * Decrypts the encrypted data passed in $data using the stored $encryptionKey. The algorithm used
	 * is AES-256, formerly known as Rijndael, and is supported by the Mcrypt library. The key
	 * for this must be less than or equal to 256 bits, or 32 characters.
	 * 
	 * @param string $data
	 * @return mixed
	 */
	protected function decrypt( $data )
	{
		// generate initialization vector for AES
		$size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
		$iv = mcrypt_create_iv( $size, MCRYPT_RAND );
	
		// decrypt with AES-256 (Rijndael)
		$rep = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->encryptionKey, $data, MCRYPT_MODE_ECB, $iv );
		
		// return the decrypted data
		return $rep;
	}
	
}
?>