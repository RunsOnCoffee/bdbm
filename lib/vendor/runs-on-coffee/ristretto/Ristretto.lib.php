<?php
/*
 * Ristretto - a general purpose PHP object library
 * Copyright (C) 2017 Nicholas Costa. All rights reserved.
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301  USA
 * 
 */

function loadRistrettoClass( $class )
{
	// strip namespace from class name
	if( strpos( $class, '\\' ) ) $class = substr( $class, strrpos( $class, '\\' ) + 1 );

	// check if class exists
	if( class_exists( $class, false ) ) return;

	$rsDir = dirname( __FILE__ );
	$classFile = $rsDir . '/src/' . $class . '.php';
		
	if( file_exists( $classFile ) )
	{
		include_once $classFile;
		return;
	}		
}

spl_autoload_register( 'loadRistrettoClass' );

?>