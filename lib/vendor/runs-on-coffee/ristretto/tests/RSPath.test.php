#!/usr/bin/php
<?php
include_once "../src/RSPath.php";
use Ristretto\RSPath;

echo RSPath::expandPath( "~/bin" )."\n";

$p = new RSPath( "~/bin" );
echo "$p\n";

?>