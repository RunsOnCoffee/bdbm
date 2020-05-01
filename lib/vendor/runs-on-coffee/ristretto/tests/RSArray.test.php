#!/usr/bin/php
<?php
include_once "RSArray.class.php";

$r = array(
		array( "one", "two", "three" ),
		array( "four", "five", "six" ),
		array( 'one' => "seven", 'two' => "eight", 'three' => "I am the very model of a modern major General" ),
		);

print_r( $r );
RSArray::textTable( $r );
?>