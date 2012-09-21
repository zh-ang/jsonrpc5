<?php

require(dirname(__FILE__)."/../library/Jsonrpc5/include.php");

require(dirname(__FILE__)."/math.class.php");
require(dirname(__FILE__)."/sample.class.php");

$server = new Jsonrpc5_Service();
$server->register(new math);
$server->register(new sample);
$server->handle();
