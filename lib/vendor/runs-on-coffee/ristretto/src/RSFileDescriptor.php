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
 * Representation of a file descriptor. Provides utility functions for getting
 * characteristics such as file type, queue order, etc.
 *
 * Based on code from here:
 * https://stackoverflow.com/questions/11327367/detect-if-a-php-script-is-being-run-interactively-or-not
 *
 * @author Nicholas Costa <ncosta@alum.rpi.edu>
 * @package Ristretto
 * @version 0.1
 */
class RSFileDescriptor
{
	public	$fd;
	public	$info;

    private function getMode( $fd )
    {
        $stat = fstat( $fd );
        $mode = $stat[ 'mode' ] & 0170000; 		// S_IFMT

        $this->info = new StdClass;

        $this->info->isFifo = $mode == 0010000; // S_IFIFO
        $this->info->isChr  = $mode == 0020000; // S_IFCHR
        $this->info->isDir  = $mode == 0040000; // S_IFDIR
        $this->info->isBlk  = $mode == 0060000; // S_IFBLK
        $this->info->isReg  = $mode == 0100000; // S_IFREG
        $this->info->isLnk  = $mode == 0120000; // S_IFLNK
        $this->info->isSock = $mode == 0140000; // S_IFSOCK
    }

    public function __construct( &$fd )
    {
        $this->getMode($this->stdin,  STDIN);
    }
    
    public function isTTY()
    {
    	if( !function_exists( 'posix_isatty' ) )
    	{
    		return null;
    	}
    	
    	if( posix_isatty( $this->fd ) ) return true;
    	else return false;
    }
}

?>