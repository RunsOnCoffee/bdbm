#!/usr/bin/env php
<?php
include_once "RSArguments.class.php";

$params = RSArguments::getopt( "ab:c" );
print_r( $params );



?>